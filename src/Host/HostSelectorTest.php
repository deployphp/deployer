<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Exception\Exception;

class HostSelectorTest extends TestCase
{
	 public function testHostSelectorByName()
    	{
		$HostSelector= new HostSelector();
		//Declaration of variables 
		$Host1 = new Host('Host1');
			$Host1
				->stage('stage')
				->roles('db', 'app');
		$Host2 = new Host('Host2');
			$Host2
				->stage('stage2')
				->roles('db', 'app');
		$Hosts= array($Host1,$Host2);
	
		$HostSelector->__construct($Hosts, $defaultStage = null);

		self::assertEquals($Host1, getHosts('stage'));
       		self::assertEquals($Hosts, getByRoles(['db', 'app']));

	}
}
?>
