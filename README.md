auto-deploy-from-git
====================

Here's how to set up automated deploying of codes from GitHub or BitBucket. After pushing a commit from your local machine, your server will get a POST request from GitHub/BitBucket and will make a pull request to update the codes on the server.

Server Setup
--
First, you need to install Git if you have not done so

```ssh
sudo apt-get install git-core
```

As the script will be executed by apache, you'll need to run the following commands under the www-data user. To run commands under that user:

```ssh
su www-data
```
You will need to create public keys to authenticate with BitBucket without entering password in the prompt. To create ssh keys (create folder if folder do not exists):
```ssh
cd /var/www/.ssh/
ssh-keygen -t rsa
```
When prompted for keyname, just leave the default as id_rsa and hit enter. When prompt for passphrase, hit enter to create a passwordless passphrase, to allow the script to be able to authenticate with BitBucket without a password

A public key will be generated and you will need to copy the contents. This command will print out the contents of the file onto the console:

```ssh
cat id_rsa.pub
```

Select and copy the contents then go to BitBucket > your repo > Settings > Deployment keys. Click "Add Key" and paste the contents that you have copied from the public key.

Back on your server, you need to create a config file under the .ssh folder earlier.

```ssh
nano config
```

Paste this 2 lines of code into the config file:

```ssh
Host bitbucket.org
 IdentityFile ~/.ssh/id_rsa
```

This will allow Bitbucket to verify your identity automatically, without prompting you for a password. Now you can clone the repo onto the server. Select the ssh url for your repo

```ssh
git clone git@bitbucket.org:username/repo.git
```

Next, place the bitbucket.php file onto your server. Edit the contents of the file to match your server configuration.

Go to the repo page on BitBucket > Settings > Hooks. Select POST in the dropdown and click "Add Hook". Paste the URL of the bitbucket.php file that can be accessed via the web.

That's the end of the setup! Next time you need to make changes to the codes on your server, push a commit to BitBucket and your server will automatically pull the latest updates.


Troubleshooting
--
If you encounter any errors, check that the group and owner of the folder belows to **www-data** and is writable.


Credits
--
I have make some changes to this tutorial based on http://f6design.com/journal/2013/11/19/automated-git-deployments-from-bitbucket/
