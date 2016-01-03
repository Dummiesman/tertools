<?php
  function binaryReadUInt($f){
    $data = unpack("L",fread($f,4));
    return $data[1];
  }
  function binaryReadUByte($f){
    $data = unpack("C",fread($f,1));
    return $data[1];
  }
  function binaryReadUShort($f){
    $data = unpack("S",fread($f,2));
    return $data[1];
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