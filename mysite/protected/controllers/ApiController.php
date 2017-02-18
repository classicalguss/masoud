<?php

class ApiController extends Controller
{
	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
				else
					$this->render('error', $error);
		}
	}
	
	/**
	 * For registering User through API
	 */
	public function actionRegister() 
	{
		
		$device_token = Yii::app()->request->getParam('device_token',null);
		$device_type 	= Yii::app()->request->getParam('device_type',null);
		
		if ($device_token == null)
		{
			echo json_encode(array('status' =>'error','error'=>'device_token not provided.'));
			Yii::app()->end();
		}
		else if ($device_type == null)
		{
			echo json_encode(array('status' =>'error','error'=>'device_type not provided.'));
			Yii::app()->end();
		}
		
		$criteria = new CDbCriteria();
		$criteria->addCondition('device_token = :device_token');
		$criteria->addCondition('device_type = :device_type');
		$criteria->params = array(':device_token'=>$device_token,':device_type'=>$device_type);
		
		$user = new User();
		$user = User::model()->find($criteria);
		
		if ($user && $user->user_name != null)
		{
			echo json_encode(array('status' =>'user_exist','user_name'=>$user->user_name));
			Yii::app()->end();
		}
		
		$user = new User();
		$user->user_name = substr( md5(rand()), 0, 7);
		$user->device_type = $device_type;
		$user->device_token = $device_token;
		
		try
		{
			$user->save();
		}
		catch (CDbException $e)
		{
			echo json_encode(array('status' =>'error','error'=>$e));
			Yii::app()->end();
		}
		
		if($user->hasErrors())
		{
			echo json_encode(array('status' =>'error','error'=>$user->errors));
			Yii::app()->end();
		}
		
		echo json_encode(array('status' => 'success','user_name'=>$user->user_name));
	}
}