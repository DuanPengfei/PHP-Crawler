<?
//-------------------- use Slime --------------------
require './Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

//-------------------- use simple_html_dom --------------------
include_once('simple_html_dom.php');

//-------------------- china-pub --------------------
$app->get('/china-pub/:bookId', function ($bookId) {
  $url = "http://product.china-pub.com/" . $bookId;
  $html = new simple_html_dom();
  $html->load_file($url);

  $book = [];
  // search book_name
  foreach ($html->find('div[class=pro_book] h1') as $post) {
    $book['book_name'] = charsetReplace($post->innertext);
  }

  // search book_pic
  foreach ($html->find('div[class=pro_book_img] dl dt a[class=gray12a] img') as $post) {
    $book['book_pic'] = $post->src;
  }

  // search book_price
  foreach ($html->find('div[class=pro_buy_intr] span[class=pro_buy_pri]') as $post) {
    $book['book_price'] = charsetReplace($post->innertext);
  }

  // search book_price_vip
  foreach ($html->find('div[class=pro_buy_intr] span[class=pro_buy_sen]') as $post) {
    $book['book_price_vip'] = charsetReplace($post->innertext);
  }

  // search book_details
  foreach ($html->find('div[class=pro_r_deta] ul li') as $post) {
      $details = explode('：', charsetReplaceIgnoreSpace($post->innertext));
      /*if ($details[0] == '作者') {
        $book['book_details'][$details[0]] = trim($details[1]);
      } else {
        $details = explode('：', charsetReplace($post->innertext));
        $book['book_details'][$details[0]] = $details[1];
      }*/
      switch ($details[0]) {
        case '作者':
          $book['book_author'] = trim($details[1]);
          break;  
        case '出版日期':
          $details = explode('：', charsetReplace($post->innertext));
          $book['book_publish'] = $details[1];
          break;
      }
  }

  // search book_editor_choice
  foreach ($html->find('div[class=pro_r_deta] p') as $post)
  {
    $book['book_info'] = charsetReplace($post->innertext);
  }

  $response = json_encode($book);
  echo $response;
  //print_r($book);
});

//-------------------- dangdang --------------------
$app->get('/dangdang/:bookId', function ($bookId) {
  $url = "http://product.dangdang.com/" . $bookId . ".html";
  $html = new simple_html_dom();
  $html->load_file($url);

  $book = [];
  // search book_name
  foreach ($html->find('div[class=show_info] h1') as $h1) {
    $bookTitleWithSpan = charsetReplace($h1->innertext);
    foreach ($h1->find('span[class=head_title_name]') as $span) {
      $book['book_name'] = str_replace(charsetReplace($span->innertext), '', $bookTitleWithSpan);
    }
  }

  // search book_pic
  foreach ($html->find('div[class=show_pic] div[class=pic] img') as $post) {
    $book['book_pic'] = $post->wsrc;
  }

  // search book_price
  foreach ($html->find('div[class=show_info] i[class=m_price]') as $post) {
    $book['book_price'] = charsetReplace($post->innertext);
  }

  // search book_price_vip
  foreach ($html->find('div[class=show_info] b[class=d_price]') as $post) {
    $book['book_price_vip'] = charsetReplace($post->innertext);
  }

  // search book_details
  $i = 0;
  $flag = 0;
  $bookLeft = [];
  $bookRight = [];
  foreach ($html->find('div[class=show_info] div[class=book_messbox] div[class=clearfix m_t6]') as $post) {

    foreach($post->find('div[class=show_info_left]') as $left ) {
      if (charsetReplace($left->innertext) == '作者') {
        $flag = $i;
      }
      $bookLeft[$i] = charsetReplace($left->innertext);
    }

    foreach($post->find('div[class=show_info_right]') as $right) {
      if (isset($flag)) {
        $bookRight[$i] = charsetReplaceIgnoreSpace($right->innertext);
        unset($flag);
      } else {
        $bookRight[$i] = charsetReplace($right->innertext);
      }
    }

    $i++;
  }

  for ($j = 0; $j < count($bookLeft); $j++) {
    switch ($bookLeft[$j]) {
      case '作者':
        $book['book_author'] = $bookRight[$j];
        break;  
      case '出版时间':
        $book['book_publish'] = $bookRight[$j];
        break;
    }
    
  }

  // search book_editor_choice
  foreach ($html->find('div[id=abstract] div[class=descrip]') as $post)
  {
    $book['book_info'] = charsetReplace($post->innertext);
  }

  $response = json_encode($book);
  echo $response;
  //print_r($book);
});

function charsetReplace($str) {
  $arr = ['　' => '',
          ' ' => '',
          '	' => '',
          '&yen' => '',
          '&yen;' => '',
          '&nbsp' => '',
          '&nbsp;' => '',
          '.red14 h1 {font-size: 14px;line-height: 20px;display: inline;}' => ''
         ];
  $str = strip_tags(iconv('GBK', 'UTF-8', $str));
  return strtr($str, $arr);
}

function charsetReplaceIgnoreSpace($str) {
  $arr = ['&yen' => '',
          '&yen;' => '',
          '&nbsp' => '',
          '&nbsp;' => '',
          '.red14 h1 {font-size: 14px;line-height: 20px;display: inline;}' => ''
         ];
  $str = strip_tags(iconv('GBK', 'UTF-8', $str));
  return strtr($str, $arr);
}

$app->run();

