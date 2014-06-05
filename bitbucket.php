<?php
ini_set("log_errors", 1);
ini_set("error_log", "bitbucket_error.log");
error_reporting(E_ALL);
ignore_user_abort(true);
error_reporting(1);

/**
* The root directory where the repos live.
* 
* @var string
*/
$root_dir = '/var/www/html/';
  
/**
* A list of repo slugs that the script is allowed to know about, and their
* locations relative to $root_dir.
* 
* @var array
*/
$repos = array(
  'your_repo_name_here' => 'your_repo_name_here',
);
 
class Deploy
{
  /**
  * A callback function to call after the deploy has finished.
  * 
  * @var callback
  */
  public $post_deploy;
  
  /**
  * The name of the file that will be used for logging deployments. Set to 
  * FALSE to disable logging.
  * 
  * @var string
  */
  private $_log = '/var/www/html/bitbucket.log';
 
  /**
  * The timestamp format used for logging.
  * 
  * @link    http://www.php.net/manual/en/function.date.php
  * @var     string
  */
  private $_date_format = 'Y-m-d H:i:sP';
 
  /**
  * The name of the branch to pull from.
  * 
  * @var string
  */
  private $_branch = 'master';
 
  /**
  * The name of the remote to pull from.
  * 
  * @var string
  */
  private $_remote = 'origin';
 
  /**
  * The directory where your website and git repository are located, can be 
  * a relative or absolute path
  * 
  * @var string
  */
  private $_directory;
 
  /**
  * Sets up defaults.
  * 
  * @param  string  $directory  Directory where your website is located
  * @param  array   $data       Information about the deployment
  */
  public function __construct($directory, $options = array())
  {
      // Determine the directory path
      $this->_directory = realpath($directory);
 
      $available_options = array('log', 'date_format', 'branch', 'remote');
 
      foreach ($options as $option => $value)
      {
          if (in_array($option, $available_options))
          {
              $this->{'_'.$option} = $value;
          }
      }
 
      $this->log('Begin deployment for repo ' . $this->_directory . '...');
  }
 
  /**
  * Writes a message to the log file.
  * 
  * @param  string  $message  The message to write
  * @param  string  $type     The type of log message (e.g. INFO, DEBUG, ERROR, etc.)
  */
  public function log($message, $type = 'INFO')
  {
      echo $message;
      if ($this->_log)
      {
          // Set the name of the log file
          $filename = $this->_log;
 
          if ( !file_exists($filename) )
          {
              // Create the log file
              file_put_contents($filename, '');
 
              // Allow anyone to write to log files
              chmod($filename, 0666);
          }
 
          // Write the message into the log file
          // Format: time --- type: message
          file_put_contents($filename, date($this->_date_format).' --- '.$type.': '.$message.PHP_EOL, FILE_APPEND);
      }
  }
 
  /**
  * Executes the necessary commands to deploy the website.
  */
  public function execute()
  {
      try
      {
          // Make sure we're in the right directory
          chdir($this->_directory);
          $this->log('Changing working directory to ' . getcwd());
 
          // Discard any changes to tracked files since our last deploy
          $this->log('Reseting repository...');
          $cmd = 'git reset --hard HEAD';
          $this->log('command: ' . $cmd);
          $output = NULL;
          exec($cmd, $output);
          if ($output == NULL)    $this->log('Failed to execute git reset. Check sudoers.');
          else                    $this->log(implode(' ', $output));

          // Update the local repository
          $this->log('Pulling in changes...');
          $cmd = 'git pull '. $this->_remote . ' ' . $this->_branch;
          $this->log('command: ' . $cmd);
          $output = NULL;
          exec($cmd, $output);
          if ($output == NULL)    $this->log('Failed to execute git pull. Check sudoers.');
          else                    $this->log(implode(' ', $output));
 
          // Secure the .git directory
          $this->log('Securing .git directory... ');
          $cmd = 'chmod -R og-rx .git';
          $this->log('command: ' . $cmd);
          $output = NULL;
          exec($cmd, $output);
          $this->log(implode(' ', $output));
 
          if (is_callable($this->post_deploy))
              call_user_func($this->post_deploy, $this->_data);
 
          $this->log('Deployment successful.');
      }
      catch (Exception $e)
      {
          $this->log($e, 'ERROR');
      }
  }
 
}

$slug = NULL;
$post_data = NULL;

//Get the repo name from bitbucket
//Check in the 'payload' param if the data isn't in the post body itself
if ($slug == '') {
  $post_data = json_decode(stripslashes($_POST['payload']), TRUE);
  $slug = $post_data['repository']['slug'];
}

//Mapping repo name to a local folder name
if (array_key_exists($slug, $repos))
{
  $deploy = new Deploy($root_dir . $repos[$slug]);
  $deploy->execute();
}
elseif ($slug == '')
{
  die('No repo specified.');
}
else
{
  die('Repo "' . $slug . '" has not been set up in the deploy script.');
}
 
?>