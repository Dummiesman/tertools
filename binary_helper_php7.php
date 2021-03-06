<?php
  function binaryReadUInt($f){
    return unpack("L",fread($f,4))[1];
  }
  function binaryReadUByte($f){
    return unpack("C",fread($f,1))[1];
  }
  function binaryReadUShort($f){
    return unpack("S",fread($f,2))[1];
  }
  
  function binaryWriteUShort($f,$value){
    fwrite($f,pack("S",$value));
  }
  
  function binaryWriteUInt($f,$value){
    fwrite($f,pack("L",$value));
  }
  
  function binaryWriteUByte($f,$value){
    fwrite($f,pack("C",$value));
  }
  
  function binaryWriteString($f,$value){
    fwrite($f,pack("C",strlen($value)));
    fwrite($f,$value);
  }
?>