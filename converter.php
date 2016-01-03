<?php 
  //since PHP7 is relatively new
  if (version_compare(phpversion(), '7.0.00', '<')) {
      require 'binary_helper.php';
  }else{
     require 'binary_helper_php7.php';
  }
  
  //----------------
  //Helper functions
  //----------------
  function getTemporaryTerFilename(){
    return "TerDump/TEMPTER_" . substr(str_shuffle(MD5(microtime())), 0, 10) . ".TER";
  }
  
  function readTERHeader($fh){
      $TERVersion = binaryReadUByte($fh);
      $TERSize = binaryReadUInt($fh);
      return ["version" => $TERVersion, "size" => $TERSize];
      //if you are running an ancient PHP install
      //return array("version" => $TERVersion, "size" => $TERSize);
  }
  
  //write the ter version/size header
  function writeTERHeader($fh,$version,$size){
    binaryWriteUByte($fh,$version);
    binaryWriteUInt($fh,$size);
  }
  
  //write a blank material map
  function writeTERMaterialMap($fh,$size,$layername)
  {
    for($it = 0; $it < ($size*$size); $it++){
      binaryWriteUByte($fh,0);
    }
    //num layers + layer name
    binaryWriteUInt($fh,1);
    binaryWriteString($fh,$layername);
  }
  
  //--------------------------
  //Main conversion functions
  //--------------------------
  
  //convert from RAW to TER
  function convertRAW($fh,$origsize){
    $rawWidth = intval($_POST["dimX"]);
    $rawHeight = intval($_POST["dimY"]);
    $rawType = $_POST["endianness"];
    //cache this for reading the RAW file
    if($rawType == "big"){
      $rawType = "n";
    }elseif($rawType == "little"){
      $rawType = "v";
    }else{
      die("what kind of rawtype is this? " . $rawType);
    }
    
    if($rawWidth != $rawHeight){
      die("Heightmap must be square!");
      fclose($fh);
    }
    if(($rawWidth % 2) > 0){
      die("Heightmap must be proper size (1024x1024,2048x2048,4096x4096,etc)");
      fclose($fh);
    }
    $calculated_size = (($rawHeight+1) * ($rawWidth+1)) * 2;
    if($calculated_size > $origsize){
      die("Wrong dimensions entered, too large!");
      fclose($fh);
    }
    if($calculated_size < $origsize){
      echo("<b>Warning</b> You entered dimensions smaller than the RAW size. (" . ($origsize - $calculated_size) . " leftover bytes)");
    }
    
    //well, our checks succeeded! lets continue
    $terfp = getTemporaryTerFilename();
    $terfh = fopen($terfp,"w");
    
    //write TER header
    writeTERHeader($terfh,7,$rawWidth);
      
    //write TER data
    for($y = 0; $y <=$rawHeight; $y++){
      for($x = 0; $x <= $rawWidth; $x++){
          $data = unpack($rawType,fread($fh,2));
        if($x != $rawWidth && $y != $rawHeight){
          binaryWriteUShort($terfh,$data[1]);
        }
      }
    }
    
    writeTERMaterialMap($terfh,$rawWidth,"TerrainLayer1");
    fclose($fh);
    
    //Complete, return the saved file
    header("Location: " . $terfp);
    
    //TODO : Find a way to delete these TER files after
  }
  
  //convert from PNG to TER
  function convertPNG($fp){
    $img = imagecreatefrompng($fp);
    
    //check if we have a proper image/png
    $Xsize = imagesx($img);
    $Ysize = imagesy($img);
    if($Xsize != $Ysize){
      fclose($fh);
      die("Heightmap must be square!");
    }
    if(($Xsize % 2) > 0){
      fclose($fh);
      die("Heightmap must be proper size (1024x1024,2048x2048,4096x4096,etc)");
    }
    
    //open up a file handle
    $tempfilename = getTemporaryTerFilename();
    $fh = fopen($tempfilename,"w");
    
    //write TER header
    writeTERHeader($fh,7,$Xsize);
    
    //write TER data
    for($y = 0; $y < $Ysize; $y++){
      for($x = 0; $x < $Xsize; $x++){
        //GET RGB
        $rgb = imagecolorat($img,$x,$y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        //WRITE THE AVERAGE AS A USHORT
        $val = ((($r + $g + $b) / 3) / 255) * 65535;
        binaryWriteUShort($fh,$val);
      }
    }
    
    //finish off TER
    writeTERMaterialMap($fh,$Xsize,"TerrainLayer1");
    fclose($fh);
    
    //Complete, return the saved file
    header("Location: " . $tempfilename);
    
    //TODO : Find a way to delete these TER files after
  }
  
  //convert from TER to PNG
  function convertTER($fh){
    $header = readTERHeader($fh);
    if($header["version"] != 7){
      var_dump($header);
      die("TER version incorrect! (expected 7, got " . $header["version"] . ")");
    }else{
      $size = $header["size"];
      $img =  imagecreatetruecolor ( $size, $size);
      for($y = 0; $y < $size; $y++){
          for($x = 0; $x < $size; $x++){
            $height = binaryReadUShort($fh);
            $height = ($height / 65535) * 255;
            
            $color_alloc = imagecolorallocate($img,$height,$height,$height);
            imagesetpixel($img,$x,$y,$color_alloc);
          }
      }
      fclose($fh);
      header("Content-type: image/png");
      imagepng($img);
      die();
    }
  }
  
  //----------------
  //Main script body
  //----------------
  
  //did the user play with things they shouldn't have?
  if (!isset($_GET["type"]) || !isset($_FILES['fileinput'])){
      die("Can't call this standalone... :|");
  }
  
  $filetype = $_GET["type"];
  
  //lazy GET way for finding filetype
  if($filetype != "TER" && $filetype != "PNG" && $filetype != "RAW"){
    die("Wrong filetype!");
  }
  //continuing on

  $file_tmp = $_FILES['fileinput']['tmp_name'];
  
  if($filetype == "TER" || $filetype == "RAW"){
    $handle = fopen($file_tmp, "r");
    if($filetype == "TER"){
      convertTER($handle);
    }else{
      convertRAW($handle,filesize($file_tmp));
    }
  }else{
    convertPNG($file_tmp);
  };

?>