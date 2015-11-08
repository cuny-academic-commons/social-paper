<?php

/**
 * Mock BP_Follow_Updater because ugh.
 */
class CACSP_BP_Follow_Updater extends BP_Follow_Updater {
	public function run_install() {
		$this->install();
	}
}
