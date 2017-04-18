<?php
namespace Deployer\Host;

use Deployer\Exception\Exception;

//require "HostSelector.php";
class HostSelectorTest //extends TestCase
{



	public function PrintResult($HS , $hostnames)
	{

		$hostsByName=$HS->getByHostnames($hostnames);
		echo " results for hosts = ";
		print_r($hosts);
		echo "  and hostsnames = ";
		print_r($hostnames);
		echo "is : ";
		print_r($hostsByName);
		echo "\n ";

	}
	 public function testHostSelectorByName()
    	{

		$HS= new HostSelector();

		//Declaration of variables 
		$hosts[1] = new Host('host1');
			$hosts[1]
			    ->stage('stage')
			    ->roles('db', 'app');

		$hosts[2] = new Host('host2');
			$hosts[2]
			    ->stage('stage')
			    ->roles('db', 'app');

		$hostnames[1]='one';
		$hostnames[2]='host2';
		$hostnames[3]='null';
		$hostsnull=null;
		$hostnamesull=null;
		$hostsOneAtt[1]=new Host('host1');
			$hostsOneAtt[1]
			    ->stage('stage')
			    ->roles('db', 'app');
		$hostnameOneAtt[1]='host1';
		$hostnameOneAtt="Three";
		
		 $HS->__construct($hosts, $defaultStage = null);
		 PrintResult($HS , $hostnames);
		 PrintResult($HS , $hostnamesOneAtt);
		$HS->__construct($hostsnull, $defaultStage = null);
		 PrintResult($HS , $hostnames);
		 PrintResult($HS , $hostnamesnull);

		$HS->__construct($hostsOneAtt, $defaultStage = null);
		 PrintResult($HS , $hostnames);
	}
}
?>
