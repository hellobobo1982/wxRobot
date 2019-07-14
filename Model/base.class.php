<?php
class base
{
    public static $Db_ServerName = "";
    public static $Db_UserName = "";
    public static $Db_PassWord = "!";
    public static $DB = "";
    public static $FilePath ="Commission".DIRECTORY_SEPARATOR;
    public static $conn;

    public function __construct()
    {

        if(!isset(self::$conn) || empty(self::$conn)){
            $this->connect();
        }
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        mysqli_close(self::$conn);
        //子类中要重写构造覆盖父类的构造，可以避免数据库关闭
    }

    public function connect(){
        if(!isset(self::$conn) || empty(self::conn)){
            self::$conn = mysqli_connect(self::$Db_ServerName,self::$Db_UserName,self::$Db_PassWord);
            if(!self::$conn)
            {
                die(json_encode(array("done"=>"连接失败：" . mysqli_connect_error())));
            }
        }
        mysqli_set_charset (self::$conn,'utf8');
        mysqli_select_db(self::$conn,self::$DB);
        return self::$conn;
    }

    //长时间如果不操作数据库就会MySQL server hasgone away，需要不定时检测下
    public function ping(){
        if(!mysqli_ping(self::$conn)){
            mysqli_close(self::$conn); //注意：一定要先执行数据库关闭，这是关键
            $this->connect();
        }
    }
}
