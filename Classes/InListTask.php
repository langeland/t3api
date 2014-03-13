<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Jon Klixbüll Langeland <jon@klixbull.org>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Class BuildIndexTask
 *
 * @author Jon Klixbüll Langeland <jon@moc.net>
 */
class InListTask extends Task {

	/**
	 * @var string
	 */
	private $needle;

	/**
	 * @var string
	 */
	private $haystack;

	/**
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * @var string
	 */
	private $property;

	/**
	 * @param string $haystack
	 * @return void
	 */
	public function setHaystack($haystack) {
		$this->haystack = $haystack;
	}

	/**
	 * @return string
	 */
	public function getHaystack() {
		return $this->haystack;
	}

	/**
	 * @param string $needle
	 * @return void
	 */
	public function setNeedle($needle) {
		$this->needle = $needle;
	}

	/**
	 * @return string
	 */
	public function getNeedle() {
		return $this->needle;
	}

	/**
	 * @param string $delimiter
	 * @return void
	 */
	public function setDelimiter($delimiter) {
		$this->delimiter = $delimiter;
	}

	/**
	 * @return string
	 */
	public function getDelimiter() {
		return $this->delimiter;
	}

	/**
	 * @param string $property
	 * @return void
	 */
	public function setProperty($property) {
		$this->property = $property;
	}

	/**
	 * @return string
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @throws BuildException
	 * @return void
	 */
	public function main() {
		if ($this->property === NULL) {
			throw new BuildException('property attribute is required', $this->location);
		}

		$strawsRaw = explode($this->getDelimiter(), $this->getHaystack());
		$straws = array_map('trim', $strawsRaw);
		$return = in_array($this->getNeedle(), $straws) ? 'true' : 'false';
		$this->project->setProperty($this->getProperty(), $return);
	}

}

