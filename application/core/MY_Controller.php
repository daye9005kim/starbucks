<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class MY_Controller
 * 코어 확장
 */
class MY_Controller extends CI_Controller
{
    public $db;

    /**
     * MY_Controller constructor.
     */
    function __construct()
    {
        parent::__construct();

        $appdb = 'default';
        if ($_SERVER['HTTP_HOST'] === 'superglue-dttks.run.goorm.io') {
            $appdb = 'superglue4';
        } else if ($_SERVER['HTTP_HOST'] === 'jasoncafe-ghebk.run.goorm.io') {
			$appdb = 'jasoncafe';
		} else if ($_SERVER['HTTP_HOST'] === 'starbucks-qmtuw.run.goorm.io') {
			$appdb = 'dayecafe';
		}

        $this->db = $this->load->database($appdb, true);

		// $this->session->unset_userdata('');
//		$this->session->sess_destroy(); die();

		if (!empty($this->uri->segments) && in_array($this->uri->segments[1] . '/' . $this->uri->segments[2], array('member/login', 'member/logout'))) {
			//인증 절차 패스
			return true;
		}
		$this->load->helper('cookie');
//		var_dump(get_cookie('ci_session'));
		$user = $this->input->post('user');
		var_dump($user); die();

		if (empty($user)) {
			return $this->load->view('view', array('status' => 308, 'url' => '/member/login','data' => '로그인 해주세요.'));
		}

		$SES_USER = $this->session->userdata($user);
		if(empty($SES_USER)) {
			$this->session->set_userdata($user, array('part' => '개발팀', 'name' => $user));
		}

		$SES_USER = $this->session->userdata($user);

		$_POST['name'] = $SES_USER['name'];
		$_POST['pos'] = isset($SES_USER['pos']) ? $SES_USER['pos'] : '';
		$_POST['dept'] = isset($SES_USER['dept']) ? $SES_USER['dept'] : '';
		$_POST['team'] = isset($SES_USER['team']) ? $SES_USER['team'] : '';
		$_POST['part'] = isset($SES_USER['part']) ? $SES_USER['part'] : '';

		return true;
    }


    /**
     *
     */
    function __destruct()
    {
        if ($this->db) {
            $this->db->close();
        }
    }
}
