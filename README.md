Nog is a Javascript enabled logging system which documents the execution of 
a PHP script and logs nested functions' arguments, times and messages.
The name is short for Nested Log.

Within all functions that you want to track, do the following

1) Open the Nog.php and set $save_path to the destination folder for log files

2) Either use the static init function, or create an instance of a nog object

    Be sure to pass a string that will title you save files.

3) for all functions that you want to track:
  
    A) add this at the very start of the function:
        
         if(class_exists('Nog')){Nog::O();} 

    B) add this at the very end of the function:
        
         if(class_exists('Nog')){Nog::C();} 

    C) Use this to log all messages, arrays or objects:
        
         if(class_exists('Nog')){Nog::M('...');} 

4) Run your script

5) Check the log folder and view the logs using an web browser.



