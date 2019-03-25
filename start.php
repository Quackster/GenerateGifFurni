<?php 
class Generate_Furni
{
    private $_gif;
    private $_filename;
    private $_base = "base/";
    private $_output;
    private $_furniType;

    private $_assetsPosition = "";
    private $_assets = "";
    private $_frame = "";
    private $_frameRepeat = 2;

    public function __construct($name, $furniType)
    {
        $this->_filename = "gif_".$name;
        $this->_output = "ouput/";
        $this->_furniType = $furniType;

        $this->_gif = new Imagick("gif/".$name.".gif");
        //$this->_gif->optimizeImageLayers();

        if (!is_dir($this->_output.$this->_filename))
            mkdir($this->_output.$this->_filename);
    }

    public function start()
    {
        $this->_generateImages();
        $this->_generateDat();
    }

    private function _generateDat()
    {
        foreach(['manifest', 'assets', 'logic', 'visualization', 'index'] as $id)
        {
            $filedata = file_get_contents($this->_base.$id.".dat");
            $filedata = str_replace("{{filename}}", $this->_filename, $filedata);
            $filedata = str_replace("{{assets_position}}", $this->_assetsPosition, $filedata);
            $filedata = str_replace("{{assets}}", $this->_assets, $filedata);
            $filedata = str_replace("{{frame}}", $this->_frame, $filedata);
            $filedata = str_replace("{{framerepeat}}", $this->_frameRepeat, $filedata);


            if($this->_furniType == 1)
            {
                $filedata = str_replace("{{visu_rot}}", '<direction id="2"/>'."\n".'<direction id="4"/>', $filedata);
                $filedata = str_replace("{{logic_rot}}", '<direction id="90"/>'."\n".'<direction id="180"/>', $filedata);
                $filedata = str_replace("{{visu_layer}}", '<layer id="0" z="-3000"/>', $filedata);
            }
            else if($this->_furniType == 2)
            {
                $filedata = str_replace("{{visu_rot}}", '<direction id="2"/>'."\n".'<direction id="4"/>', $filedata);
                $filedata = str_replace("{{logic_rot}}", '<direction id="90"/>'."\n".'<direction id="180"/>', $filedata);
                $filedata = str_replace("{{visu_layer}}", '<layer id="0"/>', $filedata);
            } else {
                $filedata = str_replace("{{visu_rot}}", '<direction id="2"/>', $filedata);
                $filedata = str_replace("{{logic_rot}}", '<direction id="90"/>', $filedata);
                $filedata = str_replace("{{visu_layer}}", '<layer id="0"/>', $filedata);
            }

            if($id == 'index' || $id == "manifest")
                file_put_contents($this->_output.$this->_filename."/".$this->_filename."_".$id.".dat", $filedata);
            else
                file_put_contents($this->_output.$this->_filename."/".$this->_filename."_".$this->_filename."_".$id.".dat", $filedata);
        }
    }

    private function _generateImages()
    {
        $rot = 2;
        $i = 0;
        foreach ($this->_gif->coalesceImages() as $frame) {

            $frame->setImageCompressionQuality(85);
            //if($this->_furniType == 0)
                //$this->_resizeImage($frame, 64, 110);

            if($this->_furniType == 1)
            {
                $scale = 0.5;
                $frame->scaleimage($frame->getImageWidth() * $scale, $frame->getImageHeight() * $scale);
            }

            if($i == 0)
            {
                $icon = $this->_generateIcon($frame);
                $icon->writeImage($this->_output.$this->_filename."/".$this->_filename."_".$this->_filename."_icon_a.png");
                $icon->destroy();
            }

            if($this->_furniType != 0)
            {
                if($this->_furniType == 1)
                    $this->_convertToPoster($frame, $rot);
                else if($this->_furniType == 2)
                    $this->_convertToFloor($frame, $rot);

                $furniX = floor($frame->getImageWidth() / 2);
                $furniY = $frame->getImageWidth();
                $this->_assetsPosition .= '<asset name="'.$this->_filename.'_64_a_4_'.$i.'" flipH="1" source="'.$this->_filename.'_64_a_'.$rot.'_'.$i.'" x="'.$furniX.'" y="'.$furniY.'"/>'."\n";
            }

            $frame->writeImage($this->_output.$this->_filename."/".$this->_filename."_".$this->_filename."_64_a_".$rot."_".$i.".png");


            $furniX = floor($frame->getImageWidth() / 2);
            $furniY = $frame->getImageWidth();
            $this->_frame .= '<frame id="'.$i.'"/>'."\n";
            $this->_assetsPosition .= '<asset name="'.$this->_filename.'_64_a_'.$rot.'_'.$i.'" x="'.$furniX.'" y="'.$furniY.'"/>'."\n";
            $this->_assets .= '<asset name="'.$this->_filename.'_64_a_'.$rot.'_'.$i.'" mimeType="image/png"/>'."\n";

            $i++;
        }

        if($i >= 35)
            $this->_frameRepeat = 1;
        else
            $this->_frameRepeat = 2;
        

        if($this->_furniType == 0)
        {
            copy($this->_base."sd.png", $this->_output.$this->_filename."/".$this->_filename."_".$this->_filename."_64_sd_0_0.png");
            $this->_assetsPosition .= '<asset name="'.$this->_filename.'_64_sd_'.$rot.'_0" x="15" y="8"/>'."\n";
            $this->_assets .= '<asset name="'.$this->_filename.'_64_sd_'.$rot.'_0" mimeType="image/png"/>'."\n";
        }
    }

    private function _convertToPoster($frame, $rot)
    {
        $width = $frame->getImageWidth();
        $height = $frame->getImageHeight();

        $newHeight = $height + ($width/2);
        $im = new Imagick();
        $im->newImage($width, $newHeight, new ImagickPixel('transparent'), "png");

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                
                $newX = $x;

                if($rot == 2)
                    $newY = ($width/2) + ($y - ($x/2));
                else
                    $newY = ($y + ($x/2));


                $pixels = $frame->exportImagePixels($x, $y, 1, 1, 'RGBA', Imagick::PIXEL_CHAR);
                $im->importImagePixels($newX, $newY, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);
            }
        }

        $frame->setImage($im);
    }

    private function _convertToFloor($frame, $rot)
    {
        $width = $frame->getImageWidth();
        $height = $frame->getImageHeight();

        $newWidth = $width + ($height*2);
        $newHeight = $height + ($width/2) + 2;
        $im = new Imagick();
        $im->newImage($newWidth, $newHeight, new ImagickPixel('transparent'), "png");

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                
                $newX = $x;
                $newY = $y;

                if($rot == 2)
                    $newX = ($x + ($y*2));
                else
                    $newX = ($width*2) + ($x - ($y*2));

                if($rot == 2)
                    $newY = ($width/2) + ($y - ($x/2));
                else
                    $newY = ($y + ($x/2));

                $pixels = $frame->exportImagePixels($x, $y, 1, 1, 'RGBA', Imagick::PIXEL_CHAR);
                $im->importImagePixels($newX, $newY, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);

                if($pixels[3] != 0)
                {
                    $im->importImagePixels($newX+1, $newY+2, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);
                    $im->importImagePixels($newX+2, $newY+1, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);

                    $im->importImagePixels($newX+1, $newY+1, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);
                    $im->importImagePixels($newX+2, $newY+2, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);

                    $im->importImagePixels($newX+0, $newY+1, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);
                    $im->importImagePixels($newX+1, $newY+0, 1, 1, "RGBA", Imagick::PIXEL_CHAR, $pixels);
                }
            }
        }

        $frame->setImage($im);
    }

    private function _generateIcon($frame)
    {
        $width = $frame->getImageWidth();
        $height = $frame->getImageHeight();
        
        $scale = min(35/$width, 35/$height);
        
        // Get the new dimensions
        $desiredWidth  = ceil($scale*$width);
        $desiredHeight = ceil($scale*$height);

        $icon = new Imagick();
        $icon->newImage($desiredWidth, $desiredHeight, new ImagickPixel('transparent'), "png");
        $icon->setImage($frame);
        
        $icon->thumbnailImage($desiredWidth, $desiredHeight);
        $icon->setImagePage(35, 35, 0, 0);
        return $icon;
    }

    private function _resizeImage($frame, $w, $h)
    {
        $width = $frame->getImageWidth();
        $height = $frame->getImageHeight();
        
        $scale = min($w/$width, $h/$height);
        
        // Get the new dimensions
        $desiredWidth  = ceil($scale*$width);
        $desiredHeight = ceil($scale*$height);
        
        $frame->adaptiveResizeImage($desiredWidth, $desiredHeight);
        $frame->setImagePage($w, $h, 0, 0);
    }
}

$openDir = opendir("./gif");
while (false != ($file = readdir($openDir))) {
	if($file == '.' || $file == '..')
        continue;
    if(explode('.', $file)[1] != "gif")
        continue;
    
    $name = explode('.', $file)[0]; //NameSwf and gif


    if(strpos($name, "poster_") !== false) // 0 normal, 1 poster, 2 sol
        $type = 1;
    else if(strpos($name, "floor_") !== false)
        $type = 2;
    else
        $type = 0;

    $generateLittle = new Generate_Furni($name, $type);
    $generateLittle->Start();

    echo $name." : ".$type."\n";
}