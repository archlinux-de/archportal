#!/usr/bin/php
<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

require (__DIR__.'/../lib/Exceptions.php');
require (__DIR__.'/../lib/AutoLoad.php');

class UpdateMirrors extends CronJob {

	public function execute() {
		try {
			$status = $this->getMirrorStatus();
			if ($status['version'] != 1) {
				throw new RuntimeException('incompatible mirrorstatus version');
			}
			$mirrors = $status['urls'];
			if (empty($mirrors)) {
				throw new RuntimeException('mirrorlist is empty');
			}
			$this->updateMirrorlist($mirrors);
		} catch (RuntimeException $e) {
			echo ('Warning: UpdateMirrors failed: ' . $e->getMessage());
		}
	}

	private function updateMirrorlist($mirrors) {
		try {
			Database::beginTransaction();
			Database::query('DELETE FROM mirrors');
			$stm = Database::prepare('
			INSERT INTO
				mirrors
			SET
				host = :host,
				protocol = :protocol,
				country = :country,
				lastsync = :lastsync,
				delay = :delay,
				time = :time
			');
			foreach ($mirrors as $mirror) {
				$stm->bindParam('host', $mirror['url'], PDO::PARAM_STR);
				$stm->bindParam('protocol', $mirror['protocol'], PDO::PARAM_STR);
				$stm->bindParam('country', $mirror['country'], PDO::PARAM_STR);
				$last_sync = date_parse($mirror['last_sync']);
				$last_sync = $last_sync['error_count'] > 0 
					? null 
					: gmmktime($last_sync['hour'],
						$last_sync['minute'],
						$last_sync['second'],
						$last_sync['month'],
						$last_sync['day'],
						$last_sync['year']);
				$stm->bindParam('lastsync', $last_sync, PDO::PARAM_INT);
				$stm->bindParam('delay', $mirror['delay'], PDO::PARAM_INT);
				$stm->bindParam('time', $mirror['duration_avg'], PDO::PARAM_STR);
				$stm->execute();
			}
			Database::commit();
		} catch(RuntimeException $e) {
			Database::rollBack();
			echo ('Warning: updateMirrorlist failed: ' . $e->getMessage());
		}
	}

	private function getMirrorStatus() {
		$download = new Download(Config::get('mirrors', 'status'));

		$content = file_get_contents($download->getFile());
		if (empty($content)) {
			throw new RuntimeException('empty mirrorstatus', 1);
		}
		$mirrors = json_decode($content, true);
		if (json_last_error() != JSON_ERROR_NONE) {
			throw new RuntimeException('could not decode mirrorstatus', 1);
		}
		return $mirrors;
	}
}

UpdateMirrors::run();

?>