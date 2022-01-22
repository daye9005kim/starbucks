<?php

/**
 * 스타벅스 모델
 * Class Starbucks_model
 */
class Starbucks_model extends CI_Model
{
    /**
     * Starbucks_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 조회
     * @param $param
     * @return array()
     */
    public function select($param)
    {
        if (isset($param['content'])) {
            $param['content'] = '%' . $param['content'] . '%';
        }
        $escape = $this->db->escape($param);
        $arr = array();
        if (isset($param['product_cd'])) {
            $arr[] = sprintf('product_cd = %s', $escape['product_cd']);
        }
        if (isset($param['cate_cd'])) {
            $arr[] = sprintf('cate_cd = %s', $escape['cate_cd']);
        }

        if (isset($param['content'])) {
            $arr[] = sprintf('content like %s', $escape['content']);
        }

        $where = '';
        if (count($arr) > 0) {
            $where = 'WHERE ' . join(' AND ', $arr);
        }
        $sql = <<<SQL
SELECT product_cd, product_nm, product_img, cate_nm, cate_cd, content, caffeine, regdate 
FROM drink
{$where}
SQL;
//        echo $sql;
        $query = $this->db->query($sql);
        return $query->result_array();
    }


    /**
     * drink테이블에 값 넣기
     * @return int
     */
    public function fetch($cafe)
    {
		$success = 0;

		if (empty($cafe)) {
			return $success;
		}

		$config = $this->config->item('cafe');
		$file_name = $config[$cafe]['file_name'];

		$handle = fopen($file_name, 'a');
		file_put_contents($file_name, strtotime("now"));
		fclose($handle);

        //테이블 만들기
        $this->create();
        //삭제
        $this->delete($cafe);

        //insert
        if ($cafe === '01') {
			$menu = $this->gongcha();
		} elseif ($cafe === '02') {
			$menu = $this->pascucci();
		} elseif ($cafe === '03') {
			$menu = $this->paikdabang();
		} else {
			$menu = $this->starbucks();
		}

        foreach ($menu as $val) {
			if ($this->insert($val)) {
				$success++;
			}
		}

        return $success;
    }

	/**
	 * 스타벅스로부터 drink테이블에 값 넣기
	 * @return array
	 */
    private function starbucks()
	{
		$menu = array();
		$contents = file_get_contents('https://www.starbucks.co.kr/menu/drink_list.do');
		preg_match_all("/result = \"(W[0-9]+)\"/", $contents, $matches);

		foreach ($matches[1] as $cate_code) {

			$recv = file_get_contents('https://www.starbucks.co.kr/upload/json/menu/' . $cate_code . '.js');
			$memu = json_decode($recv, true);

			if (!isset($memu['list'])) {
				continue;
			}

			foreach ($memu['list'] as $drink) {

				if (empty($drink['product_NM'])) {
					continue;
				}
				if (strpos($drink['product_NM'], '리저브') !== false) {
					continue;
				}
				if (strpos($drink['product_NM'], '피지오') !== false) {
					continue;
				}

				$menu[] = array(
					'product_cd' => $drink['product_CD'],
					'product_nm' => $drink['product_NM'],
					'product_img' => $drink['img_UPLOAD_PATH'] . $drink['file_PATH'],
					'cate_nm' => $drink['cate_NAME'],
					'cate_cd' => $cate_code,
					'content' => $drink['content'],
					'caffeine' => $drink['caffeine'],
					'cafe' => '04',
				);
			}
		}
		return $menu;
	}

	/**
	 * 공차로부터 drink테이블에 값 넣기
	 * @return array
	 */
	private function gongcha()
	{
		$menu = array();
		$this->load->library('Simple_html_dom');

		$url = 'http://www.gong-cha.co.kr/brand/menu/product.php?c=001';
		$contents = file_get_contents($url);
		preg_match_all("/<a href\=\"\?c\=([0-9]+)\"/", $contents, $matches);

		$codes = $matches[1];
		if (empty($codes)) {
			return $menu;
		}

		foreach ($codes as $key => $code) {
			$html = file_get_html("http://www.gong-cha.co.kr/brand/menu/product.php?c={$code}");
			$pro_list_wrap = $html->find('div.pro_list_wrap');

			foreach ($pro_list_wrap as $pro_list) {
				$title = $pro_list->find('span.txt');

				foreach ($pro_list->find('img') as $i => $image) {
					$menu[] = array(
						'product_cd' => $title[$i]->plaintext,
						'product_nm' => $title[$i]->plaintext,
						'product_img' => 'http://www.gong-cha.co.kr' . $image->src,
						'cate_nm' => '',
						'cate_cd' => '',
						'content' => '',
						'caffeine' => '',
						'cafe' => '01',
					);
				}
			}
		}
		return $menu;
	}
	/**
	 * pascucci로부터 drink테이블에 값 넣기
	 * @return array
	 */
	private function pascucci()
	{
		$menu = array();
		$this->load->library('Simple_html_dom');

		$urls = array(
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00100010',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00100020',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00100030',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00100040',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00200010',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00200020',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00200030',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00200040',
			'https://www.caffe-pascucci.co.kr/product/productList.asp?typeCode=00200050',
		);

		foreach ($urls as $url) {
			$html = file_get_html($url);
			$products = $html->find('a.product');

			foreach ($products as $product) {
				$image = $product->find('img');
				$title = $product->find('h2');
				$menu[] = array(
					'product_cd' => $title[0]->plaintext,
					'product_nm' => $title[0]->plaintext,
					'product_img' => 'https://www.caffe-pascucci.co.kr' . $image[0]->src,
					'cate_nm' => '',
					'cate_cd' => '',
					'content' => '',
					'caffeine' => '',
					'cafe' => '02',
				);
			}
		}
		return $menu;
	}

	/**
	 * paikdabang로부터 drink테이블에 값 넣기
	 * @return array
	 */
	private function paikdabang()
	{
		$menu = array();
		$this->load->library('Simple_html_dom');
		$urls = array(
			'http://paikdabang.com/menu/menu_coffee',
			'http://paikdabang.com/menu/menu_drink',
			'http://paikdabang.com/menu/menu_ccino',
		);

		foreach ($urls as $url) {

			$html = file_get_html($url);

			$menu_list = $html->find('div.menu_list');
			$menu_list = $menu_list[0];

			$title = $menu_list->find('p.menu_tit');

			foreach ($menu_list->find('img') as $i => $image) {
				$menu[] = array(
					'product_cd' => $title[$i]->plaintext,
					'product_nm' => $title[$i]->plaintext,
					'product_img' => $image->src,
					'cate_nm' => '',
					'cate_cd' => '',
					'content' => '',
					'caffeine' => '',
					'cafe' => '03',
				);
			}
		}
		return $menu;
	}



    /**
     * 테이블 생성
     * @return bool
     */
    public function create()
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `drink` (
`product_cd` VARCHAR(20) NOT NULL,
`product_nm` VARCHAR(300) NULL,
`product_img` VARCHAR(500) NULL,
`cate_nm` VARCHAR(20) NULL,
`cate_cd` VARCHAR(10) NULL,
`content` TEXT NULL DEFAULT '',
`caffeine` TINYINT NOT NULL DEFAULT 0,
`cafe` char(2) NOT NULL DEFAULT '01',
`regdate` DATETIME NULL,
PRIMARY KEY (`product_cd`, `cafe`),
INDEX `product_nm` (`product_nm` ASC),
INDEX `cate_cd` (`cate_cd` ASC),
INDEX `cate_nm` (`cate_nm` ASC),
INDEX `cafe` (`cafe` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
        if ($this->db->simple_query($sql)) {
            return true;
        }
        return false;
    }


    /**
     * 테이블 삭제
     * @return bool
     */
    public function delete($cafe)
    {
    	if (empty($cafe)) {
    		return false;
		}
		$escape = $this->db->escape($cafe);

        $sql = <<<SQL
DELETE FROM drink where cafe = {$escape}
SQL;
        $this->db->query($sql);
        if ($this->db->affected_rows()) {
            return true;
        }
        return false;
    }

    /**
     * 입력
     * @param $param
     * @return bool
     */
    public function insert($param)
    {
        if (empty($param['product_cd'])) {
            return false;
        }
        if (empty($param['product_nm'])) {
            return false;
        }
		if (empty($param['cafe'])) {
			return false;
		}
        if (empty($param['product_img'])) {
            $param['product_img'] = '';
        }
        if (!isset($param['cate_nm'])) {
			$param['cate_nm'] = '';
        }
        if (!isset($param['cate_cd'])) {
			$param['cate_nm'] = '';
        }
        if (empty($param['content'])) {
            $param['content'] = '';
        }
        if (empty($param['caffeine'])) {
            $param['caffeine'] = 0;
        }

        $escape = $this->db->escape($param);
        $sql = <<<SQL
INSERT INTO drink SET 
product_cd = {$escape['product_cd']},  
product_nm = {$escape['product_nm']},  
product_img = {$escape['product_img']}, 
cate_nm = {$escape['cate_nm']},     
cate_cd = {$escape['cate_cd']},     
content = {$escape['content']},     
caffeine = {$escape['caffeine']},    
cafe = {$escape['cafe']},    
regdate = now()     
SQL;
        $this->db->query($sql);
        if ($this->db->affected_rows()) {
            return true;
        }
        return false;
    }

}
