<?php

class CACSP_UnitTestCase extends CACSP_UnitTestCase_Base {
	public function setUp() {
		parent::setUp();

		$this->factory = new CACSP_UnitTest_Factory();
	}
}
