<?php 
$target_path = "./upload1111/";//�����ļ�Ŀ¼ 

//echo "body";

$test = new test1();

$test2 = new test1();

echo $test->func2();
echo $test->func2();

echo "@@";

echo $test2->func1();

class test1
{
	public static $aa = "";
	public function func1() {
		
			
			self::$aa = 	self::$aa."aaa";
			return  self::$aa;
	}
	
		public function func2() {
			//return "word";
		self::$aa = 	self::$aa."bbb";
			
		return  self::$aa;
	}
}
?> 
