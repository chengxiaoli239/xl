<?php

namespace common\service\numberMonitor;

use InvalidArgumentException;

class NumberSchemeParser
{
    const MAX_FILE_BYTES = 1048576;
    const MAX_SCHEMES_PER_FILE = 10;
    const MAX_PATTERNS_PER_SCHEME = 10000;
    const MAX_PATTERNS_PER_FILE = 100000;

    public static function parse($content): array
    {
        if (!is_string($content) || $content === '') {
            throw new InvalidArgumentException('请选择包含号码的 TXT 文件');
        }
        if (strlen($content) > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('TXT 文件不能超过 1 MB');
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $groups = preg_split('/\R[ \t]*\R(?:[ \t]*\R)*/u', trim($content), -1, PREG_SPLIT_NO_EMPTY);
        if ($groups === false) {
            throw new InvalidArgumentException('TXT 文件编码无效，请保存为 UTF-8');
        }
        $schemes = [];
        $totalPatterns = 0;

        foreach ($groups as $group) {
            $group = str_replace(['，', '、', '；', ';', '|'], ' ', strtoupper($group));
            $group = preg_replace('/\s*,\s*/u', ',', $group);
            $patternRegex = '/(?<![0-9X])([0-9X](?:,?[0-9X]){3})(?![0-9X])/i';
            if ($group === null || preg_match_all($patternRegex, $group, $matches) === false) {
                throw new InvalidArgumentException('TXT 文件编码无效，请保存为 UTF-8');
            }
            $patterns = [];
            foreach ($matches[1] as $match) {
                $pattern = strtoupper(str_replace(',', '', $match));
                if (strlen($pattern) !== 4) {
                    continue;
                }
                $patterns[] = $pattern;
            }

            $unmatched = preg_replace($patternRegex, ' ', $group);
            $residue = preg_replace('/[,\s]+/u', '', $unmatched);
            if ($residue !== '') {
                throw new InvalidArgumentException('每个号码须为四位 0-9 或 X，且仅支持逗号或空格分隔');
            }
            if (!$patterns) {
                continue;
            }
            $fixedCounts = [];
            foreach ($patterns as $pattern) {
                $fixedCounts[strlen(str_replace('X', '', $pattern))] = true;
            }
            if (count($fixedCounts) > 1) {
                throw new InvalidArgumentException('每套方案中的号码需要使用相同定数');
            }
            if ((int)array_keys($fixedCounts)[0] < 2) {
                throw new InvalidArgumentException('仅支持二定、三定和四定号码');
            }
            if (count($patterns) > self::MAX_PATTERNS_PER_SCHEME) {
                throw new InvalidArgumentException('单套方案不能超过 10,000 组号码');
            }

            $totalPatterns += count($patterns);
            if ($totalPatterns > self::MAX_PATTERNS_PER_FILE) {
                throw new InvalidArgumentException('单个文件不能超过 100,000 组号码');
            }
            $fixedCount = 0;
            foreach (str_split(reset($patterns)) as $digit) {
                $fixedCount += $digit === 'X' ? 0 : 1;
            }
            $schemeNumber = count($schemes) + 1;
            $schemes[] = [
                'title' => '方案 ' . $schemeNumber . ' · ' . $fixedCount . ' 定',
                'fixed_count' => $fixedCount,
                'pattern_count' => count($patterns),
                'patterns' => array_values($patterns),
            ];
            if (count($schemes) > self::MAX_SCHEMES_PER_FILE) {
                throw new InvalidArgumentException('单个文件最多支持 10 套方案');
            }
        }

        if (!$schemes) {
            throw new InvalidArgumentException('没有识别到有效号码，每个号码需要包含 4 位数字或 X');
        }

        return $schemes;
    }

    public static function matchesAny(array $patterns, string $draw): bool
    {
        $draw = preg_replace('/\D/', '', $draw);
        if (strlen($draw) !== 4) {
            return false;
        }
        foreach ($patterns as $pattern) {
            if (preg_match('/^[0-9X]{4}$/', $pattern) && preg_match('/^[0-9]{4}$/', $draw)
                && preg_match('/^' . str_replace('X', '[0-9]', $pattern) . '$/', $draw)) {
                return true;
            }
        }
        return false;
    }

    public static function advance(int $status, int $streak, bool $hit): array
    {
        $nextStatus = $hit ? 1 : 2;
        return [$nextStatus, $status === $nextStatus ? $streak + 1 : 1];
    }
}
