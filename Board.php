<?php

class Board {

	protected $values = array();

	protected $candidates = array();

	public function getFirstCandidate() {
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

	public function setValueFromFirstCandidate() {
		$guess = $this->getFirstCandidate();
		$this->setValue($guess['x'], $guess['y'], $guess['value']);
	}

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

	public function setValue($x, $y, $value) {
		// echo "<b>Setting value $value to cell ($x, $y)</b><br />";
		if (isset($this->values[$x][$y])) {
			throw new Exception("Value for position ($x, $y) was set before.");
		}

		foreach ($this->getAllOtherCellsInColumn($x, $y) as $coordinate) {
			if ($this->getValue($coordinate['x'], $coordinate['y']) === $value) {
				throw new Exception("Column check: Value $value is already present in cell ({$coordinate['x']}, {$coordinate['y']})");
			}
		}
		foreach ($this->getAllOtherCellsInRow($x, $y) as $coordinate) {
			if ($this->getValue($coordinate['x'], $coordinate['y']) === $value) {
				throw new Exception("Row check: Value $value is already present in cell ({$coordinate['x']}, {$coordinate['y']})");
			}
		}
		foreach ($this->getAllOtherCellsInSection($x, $y) as $coordinate) {
			if ($this->getValue($coordinate['x'], $coordinate['y']) === $value) {
				throw new Exception("Section check: Value $value is already present in cell ({$coordinate['x']}, {$coordinate['y']})");
			}
		}

		$this->values[$x][$y] = $value;
		$this->candidates[$x][$y] = true;

		$this->resolveCell($x, $y);
	}

	public function resolveCell($x, $y) {
		$value = $this->getValue($x, $y);
		if (!$value) {
			throw new Exception("No value found for cell ($x, $y)");
		}

		// echo "Removing value $value from all cells of column $x<br />";
		foreach ($this->getAllOtherCellsInColumn($x, $y) as $coordinate) {
			$this->removeCandidate($coordinate['x'], $coordinate['y'], $value);
		}
		// echo "Removing value $value from all cells of row $y<br />";
		foreach ($this->getAllOtherCellsInRow($x, $y) as $coordinate) {
			$this->removeCandidate($coordinate['x'], $coordinate['y'], $value);
		}
		// echo "Removing value $value from all cells the section<br />";
		foreach ($this->getAllOtherCellsInSection($x, $y) as $coordinate) {
			$this->removeCandidate($coordinate['x'], $coordinate['y'], $value);
		}
	}

	public function getValue($x, $y) {
		if (!isset($this->values[$x])) {
			$this->values[$x] = array();
		}
		if (!isset($this->values[$x][$y])) {
			$this->values[$x][$y] = null;
		}
		return $this->values[$x][$y];
	}

	public function getCandidates($x, $y) {
		if (!isset($this->candidates[$x])) {
			$this->candidates[$x] = array();
		}
		if (!isset($this->candidates[$x][$y])) {
			$this->candidates[$x][$y] = range(1, 9);
		}
		return $this->candidates[$x][$y];
	}

	public function setCandidates($x, $y, array $candidates) {
		if ($this->getCandidates($x, $y) === true) {
			throw new Exception('Setting candidates for a cell that has been already solved is not allowed');
		}
		$this->candidates[$x][$y] = $candidates;
	}

	public function removeCandidate($x, $y, $candidate) {
		$candidates = $this->getCandidates($x, $y);
		if (is_array($candidates)) {
			$key = array_search($candidate, $candidates);
			if ($key !== false) {
				$candidatesBefore = '{' . implode(', ', $candidates) . '}';
				unset($candidates[$key]);
				$candidatesAfter = '{' . implode(', ', $candidates) . '}';
				// echo "Removing candidate $candidate from cell ($x, $y) $candidatesBefore -> $candidatesAfter<br />";

				if (count($candidates) == 1) {
					$this->setValue($x, $y, end($candidates));
					// echo $this->printBoard();
				} else {
					$this->setCandidates($x, $y, $candidates);
				}
			}
		}
	}

	public function getAllOtherCellsInColumn($x, $y) {
		$coordinates = array();
		foreach (range(1, 9) as $key) {
			if ($key != $y) {
				$coordinates[] = array('x' => $x, 'y' => $key);
			}
		}
		return $coordinates;
	}

	public function getAllOtherCellsInRow($x, $y) {
		$coordinates = array();
		foreach (range(1, 9) as $key) {
			if ($key != $x) {
				$coordinates[] = array('x' => $key, 'y' => $y);
			}
		}
		return $coordinates;
	}

	public function getAllOtherCellsInSection($x, $y) {
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

	public function isCandidateInCell($x, $y, $candidate) {
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

	public function isUniqueCandidate($x, $y, $candidate) {
		$isUnique = 'column';
		// echo "Start checking if $candidate is candiate in column $x<br />";
		foreach ($this->getAllOtherCellsInColumn($x, $y) as $coordinate) {
			if ($this->isCandidateInCell($coordinate['x'], $coordinate['y'], $candidate)) {
				// echo "Value $candidate from ($x, $y) is also candidate in ({$coordinate['x']}, {$coordinate['y']})<br />";
				$isUnique = false;
				break;
			}
		}
		if (!$isUnique) {
			$isUnique = 'row';
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
			$isUnique = 'section';
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

	public function getUnsolvedCellCount() {
		$unsolvedCells = 0;
		foreach (range(1, 9) as $x) {
			foreach (range(1, 9) as $y) {
				if (!$this->getValue($x, $y)) {
					$unsolvedCells++;
				}
			}
		}
		return $unsolvedCells;
	}

	public function getTotalCandidateCount() {
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

	public function logicResolveAsFarAsPossible() {
		$unsolvedCellCount = 81;
		$candidateCount = 9*9*9;
		$iterations = 0;

		while ($unsolvedCellCount != 0) {

			foreach (range(1, 9) as $y) {
				foreach (range(1, 9) as $x) {
					if ($this->getValue($x, $y)) {
						// echo "<h3>Resolving cell($x, $y)</h3>";
						$this->resolveCell($x, $y);
						// echo $board->printBoard();
					}
				}
			}

			foreach (range(1, 9) as $y) {
				foreach (range(1, 9) as $x) {
					$candidates = $this->getCandidates($x, $y);
					if (is_array($candidates)) {
						foreach ($candidates as $candidate) {
							if ($where = $this->isUniqueCandidate($x, $y, $candidate)) {
								// echo "Candidate $candidate ($x, $y) is no candidate somewhere else in $where and can be set to ($x, $y)<br />";
								$this->setValue($x, $y, $candidate);
							}
						}
					}
				}
			}

			$unsolvedCellCount = $this->getUnsolvedCellCount();
			$oldCandidateCount = $candidateCount;
			$candidateCount = $this->getTotalCandidateCount();

			if ($oldCandidateCount == $candidateCount) {
				echo 'Candidate count did not change. Aborting.<br />';
				break;
			}

			$iterations++;
			if ($iterations > 5) {
				throw new Exception("Could not solve after $iterations iterations");
			}
		}
		return $candidateCount;
	}

	public function isSolved() {
		return ($this->getUnsolvedCellCount() == 0);
	}

}
