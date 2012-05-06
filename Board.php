<?php

/**
 * Sudoku board
 *
 * @author Fabrizio Branca
 * @since 2012-05-06
 */
class Board {

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var array
	 */
	protected $candidates = array();

	/**
	 * Convenience method to initialize the board with a single string
	 *
	 * @param $string
	 */
	public function setValuesAsString($string) {
		$string = preg_replace('/[^\.1-9]/', '', $string);
		$counter = 0;
		foreach (range(1, 9) as $y) {
			foreach (range(1, 9) as $x) {
				$value = $string[$counter];
				if ($value != '.') {
					$this->setValue($x, $y, $value);
				}
				$counter++;
			}
		}
	}

	/**
	 * Set value for a given cell
	 * and remove corresponding candidates from cells in same row, column and section.
	 *
	 * Before setting the value checks will be done if this is allowed
	 *
	 * @param $x
	 * @param $y
	 * @param $value
	 * @throws Exception
	 */
	public function setValue($x, $y, $value) {
		if (isset($this->values[$x][$y])) {
			throw new Exception("Value for position ($x, $y) was set before.");
		}

		$relatedCells = $this->getAllRelatedCells($x, $y);

		// do checks if the current value already exists
		foreach ($relatedCells as $coordinate) {
			if ($this->getValue($coordinate['x'], $coordinate['y']) === $value) {
				throw new Exception("Value $value is already present in cell ({$coordinate['x']}, {$coordinate['y']})");
			}
		}

		// setting the value
		$this->values[$x][$y] = $value;

		// resetting the candidates
		$this->candidates[$x][$y] = false;

		foreach ($relatedCells as $coordinate) {
			$this->removeCandidate($coordinate['x'], $coordinate['y'], $value);
		}
	}

	/**
	 * Resolve all cells as far as possible without guessing
	 *
	 * @return int candidate count
	 * @throws Exception
	 */
	public function logicResolveAsFarAsPossible() {
		$candidateCount = null;
		while (!$this->isSolved()) {
			foreach (range(1, 9) as $y) {
				foreach (range(1, 9) as $x) {
					$candidates = $this->getCandidates($x, $y);
					if (is_array($candidates)) {
						foreach ($candidates as $candidate) {
							if ($this->isUniqueCandidate($x, $y, $candidate)) {
								$this->setValue($x, $y, $candidate);
							}
						}
					}
				}
			}

			$oldCandidateCount = $candidateCount;
			$candidateCount = $this->getTotalCandidateCount();

			if ($oldCandidateCount === $candidateCount) {
				// echo 'Candidate count did not change. Aborting.<br />';
				break;
			}
		}
		return $candidateCount;
	}

	/**
	 * Try guessing until along one candidate path
	 * - until board is solved
	 * - until error (Then remove guess from original candidates. Then try again...)
	 *
	 * @return void
	 */
	public function guess() {
		$tryBoard = clone $this; /* @var $tryBoard Board */
		while (!$tryBoard->isSolved()) {
			$guess = $tryBoard->getFirstCandidate();
			echo "Trying {$guess['value']} for ({$guess['x']}, {$guess['y']})<br />";
			try {
				$tryBoard->setValue($guess['x'], $guess['y'], $guess['value']);
				$tryBoard->logicResolveAsFarAsPossible();
				if ($tryBoard->isSolved()) {
					// Case 1) Solved: OK, finished!
					$this->setValue($guess['x'], $guess['y'], $guess['value']);$board = $tryBoard;
					return;
				}
			} catch (Exception $e) {
				// Case 2) Error: Remove current candidate from original board and try another one
				echo "Did not work.<br />";
				$this->removeCandidate($guess['x'], $guess['y'], $guess['value']);
				return;
			}
			// Case 3) Not solved yet: Try additional guess ...
		}
	}

	/**
	 * Check if board is solved
	 *
	 * @return bool
	 */
	public function isSolved() {
		return ($this->getTotalCandidateCount() == 0);
	}

	/**
	 * Get html representation of current status
	 *
	 * @return string
	 */
	public function printBoard() {
		$html = '<hr /><table>';
		foreach (range(1, 9) as $y) {
			$html .= '<tr>';
			foreach (range(1, 9) as $x) {
				$html .= "<td class=\"row-$y column-$x\">";
				$value = $this->getValue($x, $y);
				if ($value) {
					$html .= '<span class="solved">'.$value.'</span>';
				} else {
					$candidates = implode(', ', $this->getCandidates($x, $y));
					$html .= '<span class="candidates">'.$candidates.'</span>';
				}
				$html .= '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		return $html;
	}

	/**
	 * Get value for a given cell.
	 * If the cell is not resolved yet null will be returned
	 *
	 * @param $x
	 * @param $y
	 * @return int|null
	 */
	protected function getValue($x, $y) {
		if (!isset($this->values[$x])) {
			$this->values[$x] = array();
		}
		if (!isset($this->values[$x][$y])) {
			$this->values[$x][$y] = null;
		}
		return $this->values[$x][$y];
	}

	/**
	 * Get candidates for a cell.
	 * (And initialize candidates on first request)
	 *
	 * @param $x
	 * @param $y
	 * @return mixed
	 */
	protected function getCandidates($x, $y) {
		if (!isset($this->candidates[$x])) {
			$this->candidates[$x] = array();
		}
		if (!isset($this->candidates[$x][$y])) {
			$this->candidates[$x][$y] = range(1, 9);
		}
		return $this->candidates[$x][$y];
	}

	/**
	 * Remove candidate from a given cell.
	 * If this results in a single candidate, the value will be set.
	 *
	 * @param $x
	 * @param $y
	 * @param $candidate
	 */
	protected function removeCandidate($x, $y, $candidate) {
		$candidates = $this->getCandidates($x, $y);
		if (is_array($candidates)) {
			$key = array_search($candidate, $candidates);
			if ($key !== false) {
				unset($candidates[$key]);
				if (count($candidates) == 1) {
					$this->setValue($x, $y, end($candidates));
				} else {
					$this->candidates[$x][$y] = $candidates;
				}
			}
		}
	}

	/**
	 * Get all related cells
	 *
	 * @param $x
	 * @param $y
	 * @return array
	 */
	protected function getAllRelatedCells($x, $y) {
		return array_merge(
			$this->getAllOtherCellsInColumn($x, $y),
			$this->getAllOtherCellsInRow($x, $y),
			$this->getAllOtherCellsInSection($x, $y)
		);
	}

	/**
	 * Get all cells of the current column (given cell excluded)
	 *
	 * @param $x
	 * @param $y
	 * @return array
	 */
	protected function getAllOtherCellsInColumn($x, $y) {
		$coordinates = array();
		foreach (range(1, 9) as $key) {
			if ($key != $y) {
				$coordinates[] = array('x' => $x, 'y' => $key);
			}
		}
		return $coordinates;
	}

	/**
	 * Get all cells of the current row (given cell excluded)
	 *
	 * @param $x
	 * @param $y
	 * @return array
	 */
	protected function getAllOtherCellsInRow($x, $y) {
		$coordinates = array();
		foreach (range(1, 9) as $key) {
			if ($key != $x) {
				$coordinates[] = array('x' => $key, 'y' => $y);
			}
		}
		return $coordinates;
	}

	/**
	 * Get all cells of the current section (given cell excluded)
	 *
	 * @param $x
	 * @param $y
	 * @return array
	 */
	protected function getAllOtherCellsInSection($x, $y) {
		$coordinates = array();
		$xRange = array(
			floor(($x-1)/3)*3+1,
			floor(($x-1)/3)*3+3,
		);
		$yRange = array(
			floor(($y-1)/3)*3+1,
			floor(($y-1)/3)*3+3,
		);
		foreach (range($yRange[0], $yRange[1]) as $keyY) {
			foreach (range($xRange[0], $xRange[1]) as $keyX) {
				if ($keyX != $x || $keyY != $y) {
					$coordinates[] = array('x' => $keyX, 'y' => $keyY);
				}
			}
		}
		return $coordinates;
	}

	/**
	 * Check if a value is a candidate for the given cell
	 *
	 * @param $x
	 * @param $y
	 * @param $candidate
	 * @return bool
	 */
	protected function isCandidateInCell($x, $y, $candidate) {
		$candidates = $this->getCandidates($x, $y);
		if (is_array($candidates)) {
			// echo "Checking if $candidate is a candiate in ($x, $y)<br />";
			return in_array($candidate, $candidates);
		} else {
			if ($this->getValue($x, $y) === $candidate) {
				return true;
				// throw new Exception("Trying a candidate that was already set before");
			}
			// echo "Checking if $candidate is a candiate in ($x, $y): No array<br />";
		}
		return false;
	}

	/**
	 * Check if a candidate is unique in a row, cell or section
	 *
	 * @param $x
	 * @param $y
	 * @param $candidate
	 * @return bool
	 */
	protected function isUniqueCandidate($x, $y, $candidate) {
		$isUnique = true;
		// echo "Start checking if $candidate is candiate in column $x<br />";
		foreach ($this->getAllOtherCellsInColumn($x, $y) as $coordinate) {
			if ($this->isCandidateInCell($coordinate['x'], $coordinate['y'], $candidate)) {
				// echo "Value $candidate from ($x, $y) is also candidate in ({$coordinate['x']}, {$coordinate['y']})<br />";
				$isUnique = false;
				break;
			}
		}
		if (!$isUnique) {
			$isUnique = true;
			// echo "Start checking if $candidate is candiate in row $y<br />";
			foreach ($this->getAllOtherCellsInRow($x, $y) as $coordinate) {
				if ($this->isCandidateInCell($coordinate['x'], $coordinate['y'], $candidate)) {
					// echo "Value $candidate from ($x, $y) is also candidate in ({$coordinate['x']}, {$coordinate['y']})<br />";
					$isUnique = false;
					break;
				}
			}
		}
		if (!$isUnique) {
			$isUnique = true;
			// echo "Start checking if $candidate is candiate in section<br />";
			foreach ($this->getAllOtherCellsInSection($x, $y) as $coordinate) {
				if ($this->isCandidateInCell($coordinate['x'], $coordinate['y'], $candidate)) {
					// echo "Value $candidate from ($x, $y) is also candidate in ({$coordinate['x']}, {$coordinate['y']})<br />";
					$isUnique = false;
					break;
				}
			}
		}
		return $isUnique;
	}

	/**
	 * Get total count of candidates
	 *
	 * @return int
	 */
	protected function getTotalCandidateCount() {
		$candidateCount = 0;
		foreach (range(1, 9) as $x) {
			foreach (range(1, 9) as $y) {
				$candidates = $this->getCandidates($x, $y);
				if (is_array($candidates)) {
					$candidateCount += count($candidates);
				}
			}
		}
		return $candidateCount;
	}

	/**
	 * Search the first candidate (needed for guessing)
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getFirstCandidate() {
		foreach (range(1, 9) as $y) {
			foreach (range(1, 9) as $x) {
				$candidates = $this->getCandidates($x, $y);
				if (is_array($candidates)) {
					return array(
						'x' => $x,
						'y' => $y,
						'value' => current($candidates),
					);
				}
			}
		}
		throw new Exception('No candidate found');
	}

}
