<?php

namespace backend\service\plans;

class PlanSinglesService
{
    public static function normalize(string $singles): string
    {
        $singles = str_replace(['‐', '‑', '‒', '–', '—', '―', '−', '﹣', '－'], '-', $singles);

        return preg_replace('/\s+/u', '', trim($singles));
    }
}
