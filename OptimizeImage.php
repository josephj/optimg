<?php
/**
 * OptimizeImage 
 * 
 * @package 
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Joseph Chiang <josephj6802@gmail.com> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class OptimizeImage {

    /**
     * is_image 
     * 
     * @param mixed $file_path 
     * @static
     * @access public
     * @return void
     */
    public static function is_image($file_path)
    {
        if ( ! file_exists($file_path))
        {
            throw new FileNotFoundException("File or path not found: " . $file_path);
        }
        $types = array("gif", "png", "gifgif", "jpg", "jpeg", "bmp");
        exec("/usr/bin/identify -quiet -format \"%m\" $file_path", $return, $error);
        $type = ($error === 0) ? mb_strtolower($return[0]) : "";
        if ($error == 1) 
        {
            return FALSE;
        }
        if (substr($type, 0, 6) === "gifgif") 
        {
            $type = "gifgif";
        }
        return in_array($type, $types);
    }

    /**
     * get_type 
     * 
     * @param mixed $file_path 
     * @static
     * @access public
     * @return void
     */
    public static function get_type($file_path)
    {
        if ( ! self::is_image($file_path))
        {
            throw new FileNotImageException("It's not an image file: " . $file_path);
        }
        exec("/usr/bin/identify -quiet -format \"%m\" $file_path", $return, $error);
        $type = ($error === 0) ? mb_strtolower($return[0]) : "";
        $type = ($error === 0) ? mb_strtolower($return[0]) : "";
        if ($error == 1) 
        {
            return FALSE;
        }
        if (substr($type, 0, 6) === "gifgif") 
        {
            $type = "gifgif";
        }
        return $type;
    }

    protected $image_path;
    protected $tmp_path;
    protected $report;
    protected $options;

    public function __construct($image_path = ".", $options = array())
    {
        $this->image_path = $image_path;
        $this->options = $options;
        $this->_process();
    }

    public function __destruct()
    {
        exec("rm -rf {$this->tmp_path}");
    }

    public function is_optimized()
    {
        if (count($this->report["not_optimized"]))
        {
            return FALSE;
        }
        else 
        {
            return TRUE;
        }
    }

    public function optimize()
    {
        foreach ($this->report["not_optimized"] as $item)
        {
            copy($item["dest_file"], $item["src_file"]);
        }
        return TRUE;
    }

    public function get_report($type = "")
    {
        return $this->report; 
    }

    private function _process()
    {
        $path = realpath($this->image_path);

        // Check if the assigned file or path exists
        if ( ! is_dir($path) && ! file_exists($path))
        {
            throw new FileNotFoundException("File or path not found: " . $path);
        }
        // Get files 
        if (is_dir($path))
        {
            $handle = opendir($path);
            // FIXME : need to run recursively
            while (FALSE !== ($file = readdir($handle))) 
            {
                if (is_dir($file))
                {
                    continue;
                }
                if ( ! self::is_image($file))
                {
                    continue;
                }
                $files[] = $file;
            }
            closedir($handle);
        }
        else
        {
            if (self::is_image($path))
            {
                $files[] = $path;
            }
        }

        if ( ! count($files))
        {
            throw new NoImageFoundException("Image not found : $path");
        }

        // Build temporary directory 
        $tmp_path = microtime();
        $tmp_path = substr(md5($tmp_path), 0, 8);
        $tmp_path = "/tmp/$tmp_path";
        mkdir($tmp_path);
        $this->tmp_path = $tmp_path;
        $this->report = array(
            "all"           => array(),
            "optimized"     => array(),
            "not_optimized" => array(),
            "target_save_size"  => 0,
        );
        foreach ($files as $file)
        {
            // optimize image start
            $src_filetype = self::get_type($file);
            $src_file     = $file;
            $return       = "";
            $error        = "";
            $src_filename = pathinfo($src_file);
            $src_filename = str_replace("." . $src_filename["extension"], "", $src_filename["basename"]);
            $dest_file = "{$tmp_path}/{$src_filename}";
            switch ($src_filetype) 
            {
                case "jpg":
                case "jpeg":
                    $dest_file .= ".jpg";
                    $cmd = "/usr/bin/jpegtran -copy none -progressive -optimize $src_file > $dest_file";
                    exec($cmd, $return, $error);
                break;
                case "gif":
                case "bmp": 
                    // convert first
                    $raw_file = $dest_file . "-raw.png";
                    $dest_file .= ".png";
                    $cmd = "/usr/bin/convert $src_file $raw_file";
                    exec($cmd);
                    if ($this->options["use_png8"])
                    {
                        $this->savePng8($raw_file, $dest_file);
                    }
                    else 
                    {
                        exec("/usr/bin/pngcrush -rem alla -brute -reduce $raw_file $dest_file");
                    }
                    exec("rm -f $raw_file");
                break;
                case "gifgif":
                    $dest_file .= ".gif";
                    $cmd = "/usr/bin/gifsicle -O2 $src_file > $dest_file";
                    exec($cmd, $return, $error);
                break;
                case "png":
                    if ($this->options["use_png8"])
                    {
                        $this->savePng8($src_file, $dest_file);
                    }
                    else 
                    {
                        $cmd = "/usr/bin/pngcrush -rem alla -brute -reduce $src_file $dest_file";
                        exec($cmd, $return, $error);
                    }
                break;
                default:
                    continue;
            }
            // check whether the image is being optimized
            $src_size   = filesize($src_file);
            $dest_size  = filesize($dest_file);
            $saved_size = $src_size - $dest_size;
            $info = array(
                "src_file"   => $src_file,
                "dest_file"  => $dest_file,
                "src_size"   => $src_size,
                "dest_size"  => $dest_size,
                "saved_size" => $saved_size,
            );
            $this->report["all"][] = $info;
            if ($saved_size <= 0) 
            {
                // if the filesize can't be smaller, don't keep the optimized image in temp folder
                exec("rm -f $dest_file", $var); 
                $this->report["optimized"][] = $info;
            }
            else {
                $this->report["target_save_size"] += $saved_size;
                $this->report["not_optimized"][] = $info;
            }
        }
    }

    private function savePng8($src_file, $dest_file)
    {
        if (self::get_type($src_file) !== "png")
        {
            return FALSE;
        }
        $src_filename = pathinfo($src_file);
        $src_filename = str_replace("." . $src_filename["extension"], "", $src_filename["basename"]);

        // pngquant : quantise image into 256 colors (overwrite file)
        $cmd = "/usr/bin/pngquant -ordered 256 $src_file";
        $quant_file = str_replace(".png", "-or8.png", $src_file);
        exec($cmd);
        exec("mv $quant_file {$this->tmp_path}/{$src_filename}-quant.png");
        $quant_file = "{$this->tmp_path}/{$src_filename}-quant.png";

        // pngout : convert image from png24 to png8
        $out_file = str_replace("-quant.png", "-out.png", $quant_file);
        $cmd = "/usr/bin/pngout -c3 -d8 -y -force $quant_file $out_file";
        exec($cmd);
        exec("rm -f $quant_file");

        // pngcrush : compress image
        $cmd = "/usr/bin/pngcrush -bit_depth 8 -brute -rem alla -reduce $out_file $dest_file";
        exec($cmd);
        exec("rm -f $out_file");

        return TRUE;
    }
}
class OptimizeImageException extends Exception {};
class FileNotFoundException extends OptimizeImageException {};
class FileNotImageException extends OptimizeImageException {};
class ModuleNotFoundException extends OptimizeImageException {};
?>
