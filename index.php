<?php
<?php
session_start();

/**
 * Initialize standard chess starting position as 8x8 array.
 * Pieces: 'wP','wR','wN','wB','wQ','wK' and black 'bP'...
 * Empty squares are null.
 */
function init_board() {
    return [
        ['bR','bN','bB','bQ','bK','bB','bN','bR'], // 8
        ['bP','bP','bP','bP','bP','bP','bP','bP'], // 7
        [null,null,null,null,null,null,null,null], // 6
        [null,null,null,null,null,null,null,null], // 5
        [null,null,null,null,null,null,null,null], // 4
        [null,null,null,null,null,null,null,null], // 3
        ['wP','wP','wP','wP','wP','wP','wP','wP'], // 2
        ['wR','wN','wB','wQ','wK','wB','wN','wR'], // 1
    ];
}

function reset_game() {
    $_SESSION['board'] = init_board();
    $_SESSION['turn'] = 'w'; // 'w' or 'b'
    unset($_SESSION['winner']);
}

/** Convert algebraic like 'e2' to [row,col] with row 0..7 (0 is rank 8). */
function coord_to_index($coord) {
    if (!preg_match('/^([a-h])([1-8])$/', $coord, $m)) return null;
    $file = ord($m[1]) - ord('a'); // 0..7
    $rank = intval($m[2]); // 1..8
    $row = 8 - $rank; // rank8->row0, rank1->row7
    return [$row, $file];
}

/** Map piece codes to Unicode chess glyphs */
function piece_glyph($piece) {
    $map = [
        'wK' => '♔','wQ' => '♕','wR' => '♖','wB' => '♗','wN' => '♘','wP' => '♙',
        'bK' => '♚','bQ' => '♛','bR' => '♜','bB' => '♝','bN' => '♞','bP' => '♟',
    ];
    return $piece ? ($map[$piece] ?? '?') : '';
}

/** Return true if coordinates are on board */
function on_board($r,$c) { return $r>=0 && $r<8 && $c>=0 && $c<8; }

/** Check if path is clear between from and to (exclusive) for sliding pieces */
function is_path_clear($board, $fr, $fc, $tr, $tc) {
    $dr = $tr - $fr;
    $dc = $tc - $fc;
    $step_r = $dr === 0 ? 0 : ($dr > 0 ? 1 : -1);
    $step_c = $dc === 0 ? 0 : ($dc > 0 ? 1 : -1);
    $r = $fr + $step_r;
    $c = $fc + $step_c;
    while ($r !== $tr || $c !== $tc) {
        if ($board[$r][$c] !== null) return false;
        $r += $step_r;
        $c += $step_c;
    }
    return true;
}

/** Validate a move from algebraic strings, basic rules only.
 * Returns [true,''] if valid, or [false,'reason'].
 */
function validate_move($board, $from, $to, $turn) {
    $a = coord_to_index($from); $b = coord_to_index($to);
    if (!$a || !$b) return [false, 'Invalid coordinates'];
    [$fr,$fc] = $a; [$tr,$tc] = $b;
    if ($fr === $tr && $fc === $tc) return [false,'No movement'];

    $piece = $board[$fr][$fc];
    if (!$piece) return [false,'No piece at source'];
    $color = $piece[0]; // 'w' or 'b'
    if ($color !== $turn) return [false,"It's not your turn"];
    $ptype = $piece[1]; // e.g. 'P','K','Q','R','B','N'

    $target = $board[$tr][$tc];
    if ($target && $target[0] === $color) return [false,'Cannot capture your own piece'];

    $dr = $tr - $fr;
    $dc = $tc - $fc;
    $adr = abs($dr); $adc = abs($dc);

    switch ($ptype) {
        case 'P': // Pawn
            $dir = ($color === 'w') ? -1 : 1;
            $startRow = ($color === 'w') ? 6 : 1;
            // simple forward move
            if ($dc === 0) {
                if ($dr === $dir && $target === null) return [true,''];
                if ($fr === $startRow && $dr === 2*$dir) {
                    // two-step, ensure path clear
                    $midr = $fr + $dir;
                    if ($board[$midr][$fc] === null && $target === null) return [true,''];
                }
                return [false,'Invalid pawn forward move'];
            } else if ($adc === 1 && $dr === $dir) {
                if ($target !== null && $target[0] !== $color) return [true,'']; // capture
                return [false,'Invalid pawn capture'];
            }
            return [false,'Invalid pawn move'];
        case 'R': // Rook
            if ($fr === $tr || $fc === $tc) {
                if (is_path_clear($board,$fr,$fc,$tr,$tc)) return [true,''];
                return [false,'Path blocked for rook'];
            }
            return [false,'Invalid rook move'];
        case 'B': // Bishop
            if ($adr === $adc) {
                if (is_path_clear($board,$fr,$fc,$tr,$tc)) return [true,''];
                return [false,'Path blocked for bishop'];
            }
            return [false,'Invalid bishop move'];
        case 'Q': // Queen
            if ($fr === $tr || $fc === $tc || $adr === $adc) {
                if (is_path_clear($board,$fr,$fc,$tr,$tc)) return [true,''];
                return [false,'Path blocked for queen'];
            }
            return [false,'Invalid queen move'];
        case 'N': // Knight
            if (($adr === 2 && $adc === 1) || ($adr === 1 && $adc === 2)) return [true,''];
            return [false,'Invalid knight move'];
        case 'K': // King (no castling)
            if (max($adr,$adc) === 1) return [true,''];
            return [false,'Invalid king move'];
        default:
            return [false,'Unknown piece type'];
    }
}