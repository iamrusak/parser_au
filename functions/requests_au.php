<?php
require_once('parse_data.php');


function get_response($search_word)
{

	$headers = array(
		'cookie' => 'XSRF-TOKEN=b2f981a9-9c6c-4a04-a980-f1fbe99d9589; 
							JSESSIONID=1EF67D3D4D287C65A8C6D0508B8517C8; 
							SESSIONTOKEN=eyJhbGciOiJIUzUxMiJ9.eyJqdGkiOiIyNjdiNTY3ZC04MzBiLTRhNjAtYjRmNy05M2Y5YWQ5MjU2MDUiLCJzdWIiOiIyMDc0NzY0My03NmIxLTQ3ZmEtODhmYy01MTI0ZGI2NWFmYzIiLCJpYXQiOjE2NTIwMDEyMjAsImV4cCI6MTY1MjAzMDAyMH0.CKiQt_10u0VmGkazxZbnIROvUSdmt6TYh6hoj_T4fqhbpdhzQNb-9qI3tkudU4_fVEHvUbaBch2uinokU0VY5Q; 
							AWSALB=UYx+T7uY9o1b9BFVcPwwzRabR5h89KeCaYomPhdlJAQlVl5jksc6ijCazkipX6gFVBfwCneEqVP+FFLzmFyrRc0mVqeu6/99XR5g/YF7loZiMxJcxZ8dEAL9xO9a; 
							AWSALBCORS=UYx+T7uY9o1b9BFVcPwwzRabR5h89KeCaYomPhdlJAQlVl5jksc6ijCazkipX6gFVBfwCneEqVP+FFLzmFyrRc0mVqeu6/99XR5g/YF7loZiMxJcxZ8dEAL9xO9a; 
							SESSIONTOKEN=eyJhbGciOiJIUzUxMiJ9.eyJqdGkiOiJlM2NlNjA1MS05MDM0LTRjYmMtOTU0ZS1iNTE3ZGUzYjQ2YTQiLCJzdWIiOiJmZDUxY2QyZi1lZDA2LTQyZDMtYjg5Zi1hNmM1ZGQ2ZGZiZWQiLCJpYXQiOjE2NTIwMDU5OTEsImV4cCI6MTY1MjAzNDc5MX0.VsXUn6uKlhUETadhEiLZAK1yPs5IM879Yg6wKLHEof8xvMNqeL8CbZqX9nOQ_0ojFX9be06VWHQzdasqh5O3-Q; 
							AWSALB=74PiEdCNqlGDr0zuQGoHAU9KEh3Z9/qFIx96pXbkYvKvHlsvoOX5kaMy3x4skBGMXI/aiJ7dhnUksIyVuMUd7p3+UFOPfXFkHjHlfSkzJnHrIRy4tNbBraXJsLza; 
							AWSALBCORS=74PiEdCNqlGDr0zuQGoHAU9KEh3Z9/qFIx96pXbkYvKvHlsvoOX5kaMy3x4skBGMXI/aiJ7dhnUksIyVuMUd7p3+UFOPfXFkHjHlfSkzJnHrIRy4tNbBraXJsLza',
		'origin' => 'https://search.ipaustralia.gov.au',
		'referer' => 'https://search.ipaustralia.gov.au/trademarks/search/advanced',
		'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.54 Safari/537.36'
	);
	$data = array(
		'_csrf' => 'b2f981a9-9c6c-4a04-a980-f1fbe99d9589',
		'wv[0]' => $search_word,
	);
	$result = array();

	$first_response = \WpOrg\Requests\Requests::post('https://search.ipaustralia.gov.au/trademarks/search/doSearch', $headers, $data);
	if(!is_dir('temp/') && !file_exists('temp')){
		mkdir('temp');
	}
	file_put_contents('temp/response_main.html', $first_response->body);

	$document_main = new DiDom\Document('temp/response_main.html', true);
	$result_counter = intval($document_main->first('h2.qa-count')->text());
	if ($result_counter == 0) {
		echo "0 result returned, change word";
		return;
	}

//	Find and parse throw first(zero) page
	$tbody_arr_main = $document_main->find('tbody');
	$result = parse_data($tbody_arr_main, $result);
	echo "done page #0\n";

//	Get number of pages & request id
	$pagination_links_arr = $document_main->find('[data-gotopage]');
	$pages_count = end($pagination_links_arr)->getAttribute('data-gotopage');
	$search_id = $document_main->first('input[name="s"]')->getAttribute('value');

//	Deleting file after parse
	unlink('temp/response_main.html');

//	Parsing the remaining pages
	for ($i = 1; $i <= $pages_count; $i++) {
		sleep(1);
		$url = "https://search.ipaustralia.gov.au/trademarks/search/result?s=$search_id&p=$i";
		$response_page = \WpOrg\Requests\Requests::get($url);
		file_put_contents("temp/response_page_$i.html", $response_page->body);
		echo "done page #$i\n";
		$document_page = new DiDom\Document("temp/response_page_$i.html", true);
		$tbody_page_arr = $document_page->find('tbody');
		$result = parse_data($tbody_page_arr, $result);

		unlink("temp/response_page_$i.html");
	}

//	save result into file
	file_put_contents('result.json', json_encode($result));
}