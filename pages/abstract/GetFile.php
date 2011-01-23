<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

abstract class GetFile extends Modul implements IOutput {

	protected $compression = false;

	public function prepare() {
		$this->exitIfCached();
		$this->getParams();
	}

	protected function exitIfCached() {
		if ($this->Input->Server->isString('HTTP_IF_MODIFIED_SINCE')) {
			$this->Output->writeHeader('HTTP/1.1 304 Not Modified');
			exit;
		}
	}

	protected function getParams() {
	}

	public function showWarning($text) {
		die($text);
	}

	protected function sendFile($type, $name, $content, $disposition = 'attachment') {
		$this->Output->setContentType($type);
		$this->Output->setModified();
		$this->Output->setCompression($this->compression);
		$this->Output->writeHeader('Content-Disposition: ' . $disposition . '; filename="' . urlencode($name) . '"');
		$this->Output->writeOutput($content);
	}

	protected function sendInlineFile($type, $name, $content) {
		$this->sendFile($type, $name, $content, 'inline');
	}
}

?>
