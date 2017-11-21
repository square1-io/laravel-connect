<?php

namespace Square1\Laravel\Connect\Clients\Deploy;

use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


 class GitDeploy
{
   
     private $repository;
     
     private $branch;//default is master
    
     private $sourcePath;//folder containing the code to push
     
     public function __construct($repo, $source, $branch = 'master') {
         
         $this->repository = $repo;
         $this->branch = $branch;
         $this->sourcePath = $source;
         
     }

     public function init()
     {
         $this->runGitCommand("init ");
         $this->runGitCommand("remote add origin ".$this->repository);
         $this->runCommand("ssh -T git@bitbucket.org");
         $this->runGitCommand("fetch --tags --progress");
         $this->switchToBranch($this->branch);

     }
     
     public function push()
     {

         $this->runGitCommand("add --all ");
         
         $message = Carbon::now()->toDateTimeString();
         $this->runGitCommand("commit -m ' version created on $message' ");
         
         $this->runGitCommand("push origin ". $this->branch ." -u --force ");
     }

     private function runGitCommand($command)
     { 
        $this->runCommand("git ".$command);
     }

     private function runCommand($command)
     {
        
        $process = new Process($command);

        $process->setWorkingDirectory($this->sourcePath);

        $process->run(function ($type, $buffer) {
            if ( $type == \Symfony\Component\Process\Process::OUT ) {
                fwrite(STDOUT, $buffer);
            }
            else {
                fwrite(STDERR, $buffer);
            }
        });

        if($process->isSuccessful()){
           
        } else {
            throw new ProcessFailedException($process);
        }
         
     }
     
     private function switchToBranch($branch)
     { 
        $this->runCommand("git checkout $branch --force 2>/dev/null || git checkout -b $branch --force");
     }
    
}