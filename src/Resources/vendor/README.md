Deployer require additional packages, but can not require them through Composer due next issues:
  
* PhpSecLib does not have stable enough version to require.
* Herzult/php-ssh requires `ext-ssh2`, Deployer does not.

Resources vendors must updated manually, or if you know solution create pull request. 
