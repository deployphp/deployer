# Deploy and Git

To clone your repository you need to access your git server. 
Check if you have access from your server to github with this command:

~~~bash
ssh git@github.com
~~~


There are two possibilities: deploy keys and agent forwarding.

## Deploy keys

A deploy key is a SSH key set in your repo to grant client read-only access to your repo.
As the name says, its primary function is to be used in the deploy process, where only read access is needed.
Anyone with access to the repository and server will have the ability to deploy the project.

* [Generate a ssh key](https://help.github.com/articles/connecting-to-github-with-ssh/)
* Add the ssh key to the repository's deploy keys setting
   
> Make sure your repo url uses git protocol not https, which means use `git@github.com:user/repo.git`


## Agent forwarding

In many cases, especially at the beginning of a project, 
SSH agent forwarding is the quickest and simplest method to use. 
Agent forwarding uses the same SSH keys that your local development computer uses.

**Pros**
* You do not have to generate or keep track of any new keys.
* There is no key management; users have the same permissions on the server that they do locally.

**Cons**
* Automated deploy processes can't be used.

By default, Deployer uses agent forwarding:

~~~php
host(...)
    ->forwardAgent()
~~~
