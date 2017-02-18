<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	/**
	 * Authenticates a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 * @return boolean whether authentication succeeds.
	 */
	private $_id;
	public $user_name;
	public $device_token;
	
	/**
	 * Constructor.
	 * @param string $username username
	 * @param string $password password
	 */
	public function __construct($user_name,$device_token)
	{
		$this->user_name=$user_name;
		$this->device_token=$device_token;
	}
	
	public function authenticate()
	{
		$record = User::model()->findByAttributes(array('user_name'=>$this->user_name));
		if($record===null)
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		else if ($this->device_token!==$record->device_token)
		{
			$this->errorCode=3; //Wrong device token
		}
		else
		{
			$this->_id = $record->id;
			$this->setState('user_name',$record->user_name);
			$this->setState('id',$record->id);
			$this->errorCode=self::ERROR_NONE;
		}
		
		return !$this->errorCode;
	}
	
	public function getId()
	{
		return $this->_id;
	}
}