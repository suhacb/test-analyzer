<?php

namespace App\Services;

class TestReportParser
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function parse(string $absolutePath): array
    {
        $rows = $this->readFirstTable($absolutePath);
        $fields = $this->extractFields($rows);

        $scenarioRaw   = $fields['Naziv testnega scenarija:'] ?? '';
        $userStoryRaw  = $fields['Testni scenarij:'] ?? '';
        $acRaw         = $fields['_ac_raw'] ?? '';

        $scenarioCode  = $this->extractByPattern($scenarioRaw, '/UZ\d+-KS-\d+-TS-?\d+/i');
        $acCode        = $this->deriveAcCode($scenarioCode)
                         ?: $this->extractByPattern($acRaw, '/UZ\d+-KS-\d+/i');
        $userStoryCode = $this->extractByPattern($acCode ?: $userStoryRaw, '/UZ\d+/i');

        if ($userStoryCode === '' || $acCode === '' || $scenarioCode === '') {
            throw new \RuntimeException(
                "Could not extract codes — US:{$userStoryCode} AC:{$acCode} TS:{$scenarioCode} — file:{$absolutePath}"
            );
        }

        return [
            'user_story_code'  => $userStoryCode,
            'user_story_title' => $this->titleAfterDash($userStoryRaw),
            'ac_code'          => $acCode,
            'ac_title'         => $this->titleAfterDash($acRaw),
            'scenario_code'    => $scenarioCode,
            'scenario_title'   => $this->titleAfterDash($scenarioRaw),
            'user_role'        => $this->nullable($fields['Uporabniška vloga:'] ?? ''),
            'preconditions'    => $this->nullable($fields['Predpogoji, ki morajo biti upoštevani:'] ?? ''),
            'test_steps'       => $this->nullable($fields['_test_steps'] ?? ''),
            'expected_result'  => $this->nullable($fields['_expected_result'] ?? ''),
            'outcome_raw'      => $this->nullable($fields['Ugotovitev'] ?? ''),
            'outcome'          => $this->normaliseOutcome($fields['Ugotovitev'] ?? ''),
            'comments'         => $this->nullable($fields['Opombe'] ?? ''),
            'tested_at'        => $this->parseDate($fields['Datum in čas testiranja:'] ?? ''),
            'browser'          => $this->nullable($fields['Brskalnik ali mobilna naprava'] ?? ''),
            'tester_name'      => $this->nullable($fields['Ime in priimek, organizacija'] ?? ''),
        ];
    }

    // -------------------------------------------------------------------------
    // XML extraction
    // -------------------------------------------------------------------------

    private function readFirstTable(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Cannot open ZIP: {$path}");
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException("word/document.xml not found in: {$path}");
        }

        $dom = new \DOMDocument();
        @$dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS);

        $rows = [];

        foreach ($xpath->query('//w:tbl[1]/w:tr') as $tr) {
            $cells = [];
            foreach ($xpath->query('w:tc', $tr) as $tc) {
                $paragraphs = [];
                foreach ($xpath->query('w:p', $tc) as $p) {
                    $text = '';
                    foreach ($xpath->query('.//w:t', $p) as $t) {
                        $text .= $t->nodeValue;
                    }
                    if ($text !== '') {
                        $paragraphs[] = $text;
                    }
                }
                $cells[] = $this->clean(implode("\n", $paragraphs));
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Field extraction (label-based, immune to column-span variations)
    // -------------------------------------------------------------------------

    private function extractFields(array $rows): array
    {
        $fields = [];
        $afterStepsHeader = false;

        foreach ($rows as $cells) {
            if ($afterStepsHeader) {
                $fields['_test_steps']      = $cells[0] ?? '';
                $fields['_expected_result'] = $cells[1] ?? '';
                $afterStepsHeader = false;
                continue;
            }

            // Scan every cell for known labels; value is always the very next cell.
            foreach ($cells as $ci => $cell) {
                $label = $this->normaliseLabel($cell);

                switch ($label) {
                    case 'testni scenarij':
                        $fields['Testni scenarij:'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'kriterij sprejemljivosti':
                        $fields['_ac_raw'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'naziv testnega scenarija':
                        $fields['Naziv testnega scenarija:'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'uporabniška vloga':
                        $fields['Uporabniška vloga:'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'predpogoji, ki morajo biti upoštevani':
                        $fields['Predpogoji, ki morajo biti upoštevani:'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'koraki testiranja':
                        $afterStepsHeader = true;
                        break;

                    case 'ugotovitev':
                        $fields['Ugotovitev'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'opombe':
                        $fields['Opombe'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'datum in čas testiranja':
                        $fields['Datum in čas testiranja:'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'brskalnik ali mobilna naprava':
                        $fields['Brskalnik ali mobilna naprava'] = $cells[$ci + 1] ?? '';
                        break;

                    case 'ime in priimek, organizacija':
                        $fields['Ime in priimek, organizacija'] = $cells[$ci + 1] ?? '';
                        break;
                }
            }
        }

        return $fields;
    }

    // -------------------------------------------------------------------------
    // String helpers
    // -------------------------------------------------------------------------

    private function clean(string $value): string
    {
        // Remove non-breaking spaces and trim
        return trim(str_replace("\xc2\xa0", ' ', $value));
    }

    private function normaliseLabel(string $cell): string
    {
        return rtrim(mb_strtolower($this->clean($cell)), ':');
    }

    private function extractByPattern(string $value, string $pattern): string
    {
        preg_match($pattern, $value, $m);
        return $m[0] ?? '';
    }

    private function deriveAcCode(string $scenarioCode): string
    {
        // UZ15-KS-018-TS-01 → UZ15-KS-018
        preg_match('/^(UZ\d+-KS-\d+)/i', $scenarioCode, $m);
        return $m[1] ?? '';
    }

    private function titleAfterDash(string $value): string
    {
        $cleaned = $this->clean($value);
        // Handle en dash (–), em dash (—), or spaced hyphen ( - )
        if (preg_match('/(?:–|—|-)\s+(.+)$/su', $cleaned, $m)) {
            return trim($m[1]);
        }
        return $cleaned;
    }

    private function nullable(string $value): ?string
    {
        $v = $this->clean($value);
        return $v === '' ? null : $v;
    }

    private function normaliseOutcome(string $raw): string
    {
        $upper = mb_strtoupper($raw);

        if (str_contains($upper, 'NI OK') || str_contains($upper, 'NEUSPE')) {
            return 'fail';
        }

        if (str_contains($upper, 'DELNO OK')) {
            return 'soft_pass';
        }

        if (str_contains($upper, 'TEST OK') || str_contains($upper, 'USPE')) {
            return 'pass';
        }

        return 'pending';
    }

    private function parseDate(string $raw): ?string
    {
        // Extract the first dd.mm.yyyy pattern, ignoring any surrounding time/text
        if (!preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/', $this->clean($raw), $m)) {
            return null;
        }

        $dt = \DateTime::createFromFormat('j.n.Y', "{$m[1]}.{$m[2]}.{$m[3]}");

        return $dt !== false ? $dt->format('Y-m-d') : null;
    }
}
