>>> Scripts to get the size os files before compression <<<

1) Copy both scripts to /etc/bacula/scripts and grant permissions a+x


2) Run only once the script "_webacula_update_filesize_first.sh" to create informations from old jobs"
   This process can be slow.

3) Include a "RunScript" AfterJob in JobDefs to run in all Jobs and restart bacula

JobDefs {
  ...
  RunScript {
     Command = "/etc/bacula/scripts/_webacula_update_filesize.sh %i %t %e"
     RunsWhen = After
     RunsOnClient = no
     RunsOnFailure = yes
     RunsOnSuccess = yes
  }
  ...
}

4) When a Job finished, the script will read the original size of files from table File stored in LStat field 
and save in the webacula_job_size table.
