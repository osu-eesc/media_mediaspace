Media: MediaSpace

This module depends on emfield version 6.x-2.5 or greater

Note: For full support of modal frames you need to apply a patch to emvideo
file -> emfield/contrib/emvideo/emvideo.module

<   if (isset($extra) && ($extra != 'index.php')) {
<     $code .= '/'. $extra;
<   }
---
>   //
>   // CWSMOD - 12/1/2011 - PL
>   // Commenting this block and adding the next fixes this for mediaspace
>   // see http://drupal.org/node/1006976
>   //
>   //  if (isset($extra) && ($extra != 'index.php')) {
>   //    $code .= '/'. $extra;
>   //  }
> 
>   $args = func_get_args();
>   if (count($args) > 6 && ($args[6] != 'index.php')) {
>     $extra_args = array_splice($args, 5);
>     $code = implode('/', $extra_args);
>   }  
