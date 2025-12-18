<?php
// ScoreCalculator.php - Ported from C# ActivityMonitor.cs

class WorkLog {
    public $datetime;
    public $process;
    public $durationSec;
    public $mouseCount;
    public $keyboardCount;
    public $switchCount;
    public $idleSeconds;
    public $lockSeconds;
    public $sessionState;

    public function __construct($data) {
        $this->datetime = new DateTime($data['uptime']);
        $this->process = $data['process'];
        $this->durationSec = (int)$data['sec'];
        // Handle variations in field names if necessary (e.g. from raw DB rows)
        $this->mouseCount = (int)$data['mouse'];
        $this->keyboardCount = (int)$data['keyboard'];
        $this->switchCount = isset($data['switch_count']) ? (int)$data['switch_count'] : 0;
        $this->idleSeconds = isset($data['idle_seconds']) ? (int)$data['idle_seconds'] : 0;
        $this->lockSeconds = isset($data['lock_seconds']) ? (int)$data['lock_seconds'] : 0;
        $this->sessionState = $data['session'];
    }
}

class ScoreCalculator {

    public static function calculate($rows) {
        $logs = [];
        foreach ($rows as $row) {
            $logs[] = new WorkLog($row);
        }

        // Calculate scores per 30-min block
        return TimeBlockScoreCalculator::calculate($logs);
    }
}

class ProcessWeight {
    public static function getProductivityWeight($process, $keyboard) {
        $process = strtolower($process);

        if (strpos($process, 'antigravity') !== false || strpos($process, 'code') !== false || strpos($process, 'visual studio') !== false) {
            return 1.0;
        }

        if (strpos($process, 'excel') !== false || strpos($process, 'word') !== false || strpos($process, 'powerpoint') !== false) {
            return 0.9;
        }

        if (strpos($process, 'chrome') !== false || strpos($process, 'edge') !== false || strpos($process, 'firefox') !== false) {
            return $keyboard > 0 ? 0.8 : 0.6;
        }

        if (strpos($process, 'line') !== false || strpos($process, 'discord') !== false || strpos($process, 'slack') !== false) {
            return 0.4;
        }

        return 0.3;
    }
}

class ProductivityCalculator {
    public static function calculate($logs) {
        if (empty($logs)) return 0;

        $densitySum = 0;
        $weightSum = 0;
        $count = count($logs);
        
        $continuityScore = self::calculateContinuity($logs);
        
        $allUnlock = true;
        foreach($logs as $l) {
            if ($l->sessionState != "SessionUnlock") {
                $allUnlock = false;
                break;
            }
        }
        $sessionScore = $allUnlock ? 1.0 : 0.5;

        $totalDuration = 0;
        foreach($logs as $l) $totalDuration += $l->durationSec;
        if ($totalDuration == 0) return 0;

        foreach ($logs as $log) {
            if ($log->durationSec <= 0) continue;

            $density = ($log->keyboardCount + $log->mouseCount * 0.5) / $log->durationSec;
            $densitySum += self::normalizeDensity($density);
            $weightSum += ProcessWeight::getProductivityWeight($log->process, $log->keyboardCount);
        }

        $densityAvg = $densitySum / $count;
        $weightAvg = $weightSum / $count;

        return round(100 * $densityAvg * $weightAvg * $continuityScore * $sessionScore, 1);
    }

    private static function normalizeDensity($density) {
        if ($density >= 0.15) return 1.0;
        if ($density >= 0.08) return 0.8;
        if ($density >= 0.03) return 0.5;
        return 0.2;
    }

    private static function calculateContinuity($logs) {
        $switches = 0;
        $count = count($logs);
        for ($i = 1; $i < $count; $i++) {
            if ($logs[$i]->process != $logs[$i - 1]->process) {
                $switches++;
            }
        }

        if ($switches <= 3) return 1.0;
        if ($switches <= 6) return 0.8;
        if ($switches <= 10) return 0.5;
        return 0.2;
    }
}

class FocusCalculator {
    public static function calculate($logs) {
        if (empty($logs)) return 0;

        $immersion = self::calculateImmersion($logs);
        $interruption = self::calculateInterruption($logs);
        $stability = self::calculateStability($logs);

        $baseScore = 100 * $immersion * $interruption * $stability;

        $totalDuration = 0;
        $totalSwitches = 0;
        $totalIdle = 0;

        foreach ($logs as $l) {
            $totalDuration += $l->durationSec;
            $totalSwitches += $l->switchCount;
            $totalIdle += $l->idleSeconds;
        }

        // 1. Switch Penalty
        $switchPenalty = 0.0;
        if ($totalSwitches > 10) {
            $switchPenalty = min(1.0, ($totalSwitches - 10) / 40.0);
        }

        // 2. Idle Penalty
        $idlePenalty = 0.0;
        if ($totalDuration > 0) {
            $idleRatio = $totalIdle / $totalDuration;
            if ($idleRatio > 0.3) {
                $idlePenalty = min(1.0, ($idleRatio - 0.3) * 2.5);
            }
        }

        $totalPenaltyFactor = 1.0 - ($switchPenalty * 0.4 + $idlePenalty * 0.6);
        if ($totalPenaltyFactor < 0.1) $totalPenaltyFactor = 0.1;

        return round($baseScore * $totalPenaltyFactor, 1);
    }

    private static function calculateImmersion($logs) {
        $maxContinuous = 0;
        $current = 0;
        $currentProcess = "";

        foreach ($logs as $log) {
            if ($log->process == $currentProcess) {
                $current += $log->durationSec;
            } else {
                $maxContinuous = max($maxContinuous, $current);
                $current = $log->durationSec;
                $currentProcess = $log->process;
            }
        }
        $maxContinuous = max($maxContinuous, $current);

        if ($maxContinuous >= 1500) return 1.0;
        if ($maxContinuous >= 900) return 0.8;
        if ($maxContinuous >= 300) return 0.5;
        return 0.2;
    }

    private static function calculateInterruption($logs) {
        $interruptions = 0;
        foreach ($logs as $l) {
            $p = strtolower($l->process);
            if (strpos($p, 'line') !== false || 
                strpos($p, 'explorer') !== false || 
                strpos($p, 'searchapp') !== false || 
                strpos($p, 'applicationframehost') !== false) {
                $interruptions++;
            }
        }

        if ($interruptions == 0) return 1.0;
        if ($interruptions <= 2) return 0.8;
        if ($interruptions <= 5) return 0.5;
        return 0.2;
    }

    private static function calculateStability($logs) {
        $durations = [];
        foreach ($logs as $l) {
            if ($l->durationSec > 0) $durations[] = $l->durationSec;
        }
        
        $count = count($durations);
        if ($count < 2) return 1.0;

        $avg = array_sum($durations) / $count;
        $sumSq = 0;
        foreach ($durations as $d) {
            $sumSq += pow($d - $avg, 2);
        }
        $variance = $sumSq / $count;
        $stdDev = sqrt($variance);

        if ($stdDev < $avg * 0.3) return 1.0;
        if ($stdDev < $avg * 0.6) return 0.7;
        return 0.4;
    }
}

class ActivityCommentGenerator {
    public static function generate($summary) {
        $idleRatio = $summary['blockSeconds'] > 0 ? $summary['idleSeconds'] / $summary['blockSeconds'] : 0;
        
        $recentInput = $summary['lastInputSeconds'] < 30;
        $longNoInput = $summary['lastInputSeconds'] > 120; // 300 -> 120 as per C# logic? C# used > 120 for idle check? No wait, C# logic: "longNoInput = s.LastInputSeconds > 120;"
        
        $lowIdle = $idleRatio < 0.15;
        $highIdle = $idleRatio > 0.35;
        
        $fewSwitch = $summary['windowSwitchCount'] <= 10;
        $manySwitch = $summary['windowSwitchCount'] > 30;

        if ($recentInput && $lowIdle && $fewSwitch) {
            return "操作が継続しており、割り込みも少ない集中状態でした。";
        }
        if ($recentInput && $manySwitch) {
            return "作業は継続していましたが、ウィンドウ切替が多く、割り込みが頻発していました。";
        }
        if ($highIdle && $fewSwitch && $longNoInput) {
            return "操作は少なく、ウィンドウ切替も抑えられており、思考や検討に時間を使っていた可能性があります。";
        }
        if ($highIdle && $manySwitch) {
            return "操作が断続的で、ウィンドウ切替も多く、作業が分断されやすい状態でした。";
        }
        if ($longNoInput && $highIdle) {
            return "長時間入力がなく、離席や会議などの非操作時間が多かったと考えられます。";
        }
        if (!$highIdle && $fewSwitch) {
            return "大きな中断はなく、安定した作業が行われていました。";
        }
        if (!$highIdle && !$fewSwitch) {
            return "複数の作業を並行して進めていた可能性があります。";
        }
        return "全体的に操作量が少なく、稼働が控えめな時間帯でした。";
    }
}

class TimeBlockScoreCalculator {
    public static function calculate($logs) {
        if (empty($logs)) return [];

        // Group by 30-min block
        $grouped = [];
        foreach ($logs as $log) {
            $time = $log->datetime;
            $minute = (int)$time->format('i');
            $blockMinute = $minute < 30 ? 0 : 30;
            // Create a key/timestamp for the block start
            // Clone to avoid modifying original
            $blockStart = clone $time;
            $blockStart->setTime((int)$time->format('H'), $blockMinute, 0);
            $key = $blockStart->format('Y-m-d H:i:s');
            
            if (!isset($grouped[$key])) $grouped[$key] = [];
            $grouped[$key][] = $log;
        }
        
        // Sort keys
        ksort($grouped);

        $results = [];

        foreach ($grouped as $key => $blockLogs) {
            $productivity = ProductivityCalculator::calculate($blockLogs);
            $focus = FocusCalculator::calculate($blockLogs);

            // Dominant process
            $processDuration = [];
            foreach ($blockLogs as $l) {
                if (!isset($processDuration[$l->process])) $processDuration[$l->process] = 0;
                $processDuration[$l->process] += $l->durationSec;
            }
            arsort($processDuration);
            $dominantProcess = key($processDuration);
            if (!$dominantProcess) $dominantProcess = "";

            // Comment generation stats
            $maxIdle = 0;
            $totalIdle = 0;
            $totalSwitch = 0;
            foreach ($blockLogs as $l) {
                if ($l->idleSeconds > $maxIdle) $maxIdle = $l->idleSeconds;
                $totalIdle += $l->idleSeconds;
                $totalSwitch += $l->switchCount;
            }

            $summary = [
                'blockSeconds' => 1800,
                'lastInputSeconds' => $maxIdle,
                'idleSeconds' => $totalIdle,
                'windowSwitchCount' => $totalSwitch
            ];
            
            $comment = ActivityCommentGenerator::generate($summary);

            $startTime = new DateTime($key);
            $endTime = clone $startTime;
            $endTime->modify('+30 minutes');

            $results[] = [
                'blockStart' => $startTime->format('Y-m-d H:i'),
                'blockEnd' => $endTime->format('Y-m-d H:i'),
                'p' => $productivity,
                'f' => $focus,
                'proc' => $dominantProcess,
                'c' => $comment,
                'date' => $startTime->format('Y-m-d'), // Helper for grouping
                'time_range' => $startTime->format('H:i') . '-' . $endTime->format('H:i')
            ];
        }

        return $results;
    }
}
?>
