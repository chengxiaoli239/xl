<?php
function getfiles($path){ 
    foreach(scandir($path) as $afile)
    {
        if($afile=='.'||$afile=='..'){
            if(count(scandir($afile))==2){//目录为空,=2是因为.和..存在
            rmdir($curfile);// 删除空目录 
          }
        }
        if(is_dir($path.'/'.$afile)) { 
            getfiles($path.'/'.$afile); 
        } else { 
            echo $path.'/'.$afile.'<br />'; 
        } 
    } 
} 

/** 删除所有空目录 
* @param String $path 目录路径 
*/
function rm_empty_dir($path){ 
  if(is_dir($path) && ($handle = opendir($path))!==false){ 
    while(($file=readdir($handle))!==false){// 遍历文件夹 
      if($file!='.' && $file!='..'){ 
        $curfile = $path.'/'.$file;// 当前目录 
        if(is_dir($curfile)){// 目录 
            rm_empty_dir($curfile);// 如果是目录则继续遍历 
            if(count(scandir($curfile))==2){
                //目录为空,=2是因为.和..存在
                //rmdir($curfile);// 删除空目录 
                echo $curfile.'<br>';
                $myfile = fopen($curfile."/.keep", "w");
                $txt = ".gitignore\n";
                fwrite($myfile, $txt);
                fclose($myfile);
            } 
        } 
      } 
    } 
    closedir($handle); 
  } 
} 

$folder = __DIR__; 
rm_empty_dir($folder); 
//简单的demo,列出当前目录下所有的文件
#getfiles(__DIR__);
