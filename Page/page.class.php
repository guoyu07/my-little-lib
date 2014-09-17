<?php
//首页上一页下一页尾页    第1页共7页1/5
class Page{
	protected $url;//当前页的超链接 //page.php?page=3
	protected $first=1;//第一页的页数
	protected $prev;//上一页的页数
	protected $next;//下一页的页数
	protected $page;//当前页数
	protected $total;//总页数
	protected $start;//当前页的开始条数
	protected $end;//当前页的结束条数
	protected $items;//数据总条数 传入
	protected $num;//分页数
	
	function __construct($items,$num,$url){
		$this->url=$url;//page.class.php?
		$this->items=$items;
		$this->num=$num;
		$this->total=$this->getLast();
		$this->page=$_GET['page']?(int)$_GET['page']:1;
		$this->prev=$this->getPrev();
		$this->next=$this->getNext();
		$this->start=$this->getStart();
		$this->end=$this->getEnd();
	}
	//获取最后一页的页数(总页数)
	protected function getLast(){
		//数据总条数/分页数
		return ceil($this->items/$this->num);
	}
	//获取上一页的页数
	protected function getPrev(){
		//需要使用当前也进行判断
		if($this->page>1){
			return $this->page-1;
		}else{
			return 1;
		}
	}
	//获取下一页的页数
	protected function getNext(){
		if($this->page<$this->total){
			return $this->page+1;
		}else{
			return $this->total;
		}
	
	}
	
	//获取当前数据的开始条数
	protected function getStart(){
		return  ($this->page-1)*$this->num+1;
	}
	
	//获取当前数据的结束条数
	protected function getEnd(){
		return min($this->items,(($this->page-1)*$this->num+$this->num));
	}
	
	public function showPage(){
		$string='第'.$this->page.'页&nbsp;&nbsp;共'.$this->total.'页&nbsp;&nbsp;'.$this->start.'/'.$this->end;
		
		$string.='
		<a href="'.$this->url.'page='.$this->first.'">首页</a>
		<a href="'.$this->url.'page='.$this->prev.'">上一页</a>
		<a href="'.$this->url.'page='.$this->next.'">下一页</a>
		<a href="'.$this->url.'page='.$this->total.'">尾页</a>
		
		';
		return  $string;
	}
	
	public function getOffset(){
		return ($this->page-1)*$this->num;
	}
	
	
	
	
}

//page.php?page=3
//page.php?keywordS=zenmekenenge&page=3
//$a=new page(100,8,'page.class.php?');
//echo $a->showPage();
