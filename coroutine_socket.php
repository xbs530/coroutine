<?php

//socket服务器
error_reporting(E_ALL^E_NOTICE);
$socket_handle=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
socket_bind($socket_handle,'0.0.0.0',12345);
socket_listen($socket_handle,10);
socket_set_nonblock($socket_handle);


$task_pool=new task_pool;

$task_pool->add(listen_client($socket_handle));

$task_pool->run();



function listen_client(){
	global $socket_handle,$task_pool;
	echo "server init success\r\n";
	while(true){yield;
		if($client=socket_accept($socket_handle)){
			echo "1 user connected\r\n";
			$task_pool->add(client_session($client));
		}
	}
}



function client_session($client){
	socket_set_nonblock($client);
	socket_write($client,"server:hellow !\r\n");
	while(true){yield;
		$msg=@socket_read($client,1024*1000);

		if($msg===false){
			if(false===@socket_write($client,"\x20\x08")){
				socket_shutdown($client);
				return ;
			}else{
				usleep(1000);
				continue;
			}

		}

		$msg=rtrim($msg,"\n\r");
		if($msg){
			switch($msg){
				case 'logout':
					socket_write($client,"server:logout success !\r\n");
					socket_shutdown($client);
					return;
					break;
				default:
					socket_write($client,"server: received msg [".$msg."] !\r\n");
					break;
			}
		}
	}
}



class task_pool{

	protected $task_list=array();
	
	function add(Generator $t){
		$this->task_list[]=$t;
	}
	
	function run(){
		while($this->task_list){
			foreach($this->task_list as $i=>&$task){
				$task->send('');
				if(!$task->valid()){
					unset($this->task_list[$i]);
					continue;
				}
			}
			usleep(5);
		}
	}
	
}







