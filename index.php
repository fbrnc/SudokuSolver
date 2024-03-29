<style type="text/css">
	body { font-family: Arial; }
	td { width: 50px; height: 50px; border: 1px solid black; text-align: center; color: #888; }
	.solved { font-weight: bold; font-size: 25px; color: green; }
	.row-3, .row-6 { border-bottom-width: 3px; }
	.column-3, .column-6 { border-right-width: 3px; }
	table { border-collapse: collapse; border: 3px solid black; }
</style>

<?php

require_once 'Board.php';

try {

	$board = new Board();

	/*
	$board->setValuesAsString('
	..42.79..
	...3.5...
	7...9...4
	61.....29
	..8...5..
	59.....38
	1...6...7
	...5.2...
	..29.36..
	');
	*/

	/*
	$board->setValuesAsString('
	.9.....7.
	5..7.8..9
	..1.3.2..
	.7.5.2.6.
	..2...8..
	.8.1.6.4.
	..6.4.5..
	9..8.3..6
	.5.....9.
	');
	*/

	$board->setValuesAsString('
	.1...4...
	.6..2....
	..9...7..
	54....9..
	.....3...
	1...42.6.
	.7......2
	....86.53
	..6.5....
	');

	while (!$board->isSolved()) {
		$board->logicResolveAsFarAsPossible();
		$board->guess();
	}
} catch (Exception $e) {
	echo "ERROR: " . $e->getMessage() . '<br />';
}

echo $board->printBoard();