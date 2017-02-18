<?php

// this file must be stored in:
// protected/components/WebUser.php
class WebUser extends CWebUser {
	
	// Store model to not repeat query.
	private $_model;
	
	// Return first name.
	// access it by Yii::app()->user->first_name
	function getUser_Name() {
		$user = $this->loadUser(Yii::app ()->user->id);
		return $user->user_name;
	}
	
	// Load user model.
	protected function loadUser($id = null) {
		if ($this->_model === null) {
			if ($id !== null)
				$this->_model = User::model ()->findByPk ($id);
		}
		return $this->_model;
	}
}
?>