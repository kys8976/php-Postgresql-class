<?php
/**
  PostGresql DB Class
  작성자 : 1qkakzk@naver.com
  작성 참고 문서 https://www.php.net/manual/en/function.pg-affected-rows.php
  DB설정사항
  1) charset : db생성시 셋팅명령어 => set server_encoding = 'UTF8'
**/
class DB {
    private $objDB = null;
    public $arrQuery = array();




    public function dbError($error, $query='') {
        if (@_API == 'y') {
            returnData(_API_FAIL, "오류가 발생하여 실행이 중단되었습니다.");
        }
        else {
            //발생시각
            $arrMessage[] = '['.date('Y-m-d H:i:s').']';
            //페이지 위치 추적
            $arrMessage[] = 'http_host : '.$_SERVER['HTTP_HOST'];
            $arrMessage[] = 'request_uri : '.$_SERVER['REQUEST_URI'];
            $arrMessage[] = 'URL : http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            //디비 쿼리
            $arrMessage[] = 'Query : '.htmlentities($query);
            //에러메세지
            $arrMessage[] = 'Error : '.$error;
            $message = implode('<br>', $arrMessage);
        }

        if(defined('_ADMIN_EMAIL')) {
            $headers[] = 'MIME-Version: 1.0\r\n';
            $headers[] = 'Content-type: text/html; charset=UTF-8\r\n';
            $headers[] = 'To: Administraor <'._ADMIN_EMAIL.'>\r\n';
            $headers[] = 'From: '._HOME_URL.' <system@'._LOGIN_URL.'>\r\n';
            mail(_ADMIN_EMAIL, 'Database Error', $message, implode('\r\n',$headers));
        }
        if(defined('_DISPLAY_DEBUG') && _DISPLAY_DEBUG) {
            echo $message;
        }
    }

    //생성자
    public function __construct($DBHost=_DB_IP,$DBPort=_DB_PORT, $DBUser=_DB_USER, $DBPassword=_DB_PASSWORD, $DBName=_DB_NAME) {
        try {
            $this->objDB = pg_connect("host=".$DBHost." port=".$DBPort." dbname=".$DBName." user=".$DBUser." password=".$DBPassword."")or die('connection failed');
//            $this->objDB->set_charset( "utf8" );
        } catch (Exception $error) {
            $this->dbError($error);
        }
    }

    //소멸자
    public function __destruct() {
        if($this->objDB) {
            pg_close($this->objDB);
        }
    }

    //쿼리문
    public function query($query) {
        $objResult =pg_query($this->objDB, $query);
        if(pg_last_error($this->objDB)) {
            $this->dbError(pg_last_error($this->objDB), $query);
            return false;
        }
        else {
            $this->arrQuery[] = $query;
            return $objResult;
        }
    }

    // return 1 row
    public function getRow($query) {
        $objResult = $this->query($query);
        if($objResult) {
            $arrReturn = pg_fetch_assoc($objResult);
            return $arrReturn;
        }
    }

    // return multi rows
    public function getRows($query) {
        $objResult = $this->query($query);
        if($objResult) {
            $arrResult['total'] = pg_num_rows($objResult);
            $arrList = null;
$datae=0;
            while($arrTmp =  pg_fetch_assoc($objResult)) {
$datae+=1;
                $arrList[] = $arrTmp;
if($datae==$arrResult['total']){

            $arrResult['list'] = $arrList;
            return $arrResult;
echo "<div align=left><pre>"; var_dump($arrResult); echo "</pre>"; die("<br>End</div>");
}
            }


            $arrResult['list'] = $arrList;
            return $arrResult;
        }
    }

    // return rows count
    public function getNumRows($query) {
        $objResult = $this->query($query);
        if($objResult) {
            return pg_num_rows($objResult);
        }
    }

    // insert
    public function insert($table, $arrParam = array()) {
        if(empty($arrParam)) {
            return false;
        }

        $query = "insert into ". $table;
        $arrField = array();
        $arrValue = array();
        foreach($arrParam as $field => $value) {
            $arrField[] = $field;
            $arrValue[] = $value == 'now()' ? $value : "'".$value."'";
        }
        $fields = '('.implode(',', $arrField).')';
        $values = '('.implode(',', $arrValue).')';

        $query .= $fields .' values '. $values;
        $objResult = $this->query($query);

        if($objResult) {
            return true;
        }
    }


    // table, set variables, where, limit
    public function update($table, $arrParam=array(), $arrWhere=array(), $limit='',$return_query=0) {
        if(empty($arrParam)) {
            return false;
        }
        $query = "update ".$table." set ";
        foreach($arrParam as $field => $value) {
            $arrSetData[] = $value == 'now()' ? "$field=now()" : "$field=$value";
        }
        $query .= implode(', ', $arrSetData);

        if(!empty($arrWhere)) {
            foreach($arrWhere as $field => $value) {
                $arrClause[] = "$field='$value'";
            }
            $query .= ' where '.implode(' and ', $arrClause);
        }

        if(!empty($limit)) {
            $query .= ' limit '. $limit;
        }

        $objResult = $this->query($query);

        if($objResult) {
if($return_query==1){
            return $query;
}else{
            return true;

}
        }
    }

    // table, where, limit
    public function delete($table, $arrWhere = array(), $limit='') {
        if(empty($arrWhere)) {
            return false;
        }

        $query = "delete from ".$table;
        foreach($arrWhere as $field => $value) {
            $arrClause[] = "$field = '$value'";
        }
        $query .= " where ".implode(' and ', $arrClause);

        if(!empty($limit)) {
            $query .= " limit ". $limit;
        }

        $objResult = $this->query($query);

        if($objResult) {
            return true;
        }
    }

    // check exist table
    public function existTable($name) {
         $objResult = $this->query("select 1 from $name");
         if($objResult !== false) {
             if(pg_num_rows($result) > 0) {
                 return true;
             }
             else {
                return false;
             }
         }
         else {
            return false;
         }
    }

    /*
      // Connect to the database
      pg_connect("dbname=mark host=localhost");
      // Create a sample table
      pg_query("CREATE TABLE test (a INTEGER) WITH OIDS");
      // Insert some data into it
      $res = pg_query("INSERT INTO test VALUES (1)");
      $oid = pg_last_oid($res);
    */

    public function escape($data) {
         if(!is_array($data)) {
             $data = $this->objDB->pg_escape_string($data);
         }
         else {
//             $data = array_map(array($this, 'escape'), $data);
//             $data = array_map(array($this, 'escape'), $data);
         }
         return $data;
     } 
    public function getLastId($res) {
        return pg_last_oid($res);
    }

    /*
    $sql="BEGIN;
    INSERT ...
    COMMIT;";
    $result=pg_query($conn,$sql);
    echo pg_affected_rows($result);
    */

    public function getAffected($sql) {
        $result=pg_query($this->objDB,$sql);
        return pg_affected_rows($result);
    }
}
?>
