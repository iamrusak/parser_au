<?php

function parse_data($tbody_arr, $result)
{
	foreach ($tbody_arr as $tbody) {
		$row_element = $tbody->child(1);

		$table_index = intval($row_element->first('td.table-index')->text());
		$number = $row_element->first('td.number')->text();
		$logo_url = ($row_element->has('td.image')) ? $row_element->first('td.image')->child(1)->getAttribute('src') : 'no image';
		$trademark_name = $row_element->first('td.words')->text();
		$classes = $row_element->first('td.classes')->text();

		if ($row_element->first('td.status')->has('span')) {
			$status = $row_element->first('td.status')->first('span')->text();
			preg_match('~.*(?=: )|(\w+)~', $status, $status1_matched);
			preg_match('~(?<=: ).*|(\w+)~', $status, $status2_matched);
			$status1 = $status1_matched[0];
			$status2 = $status2_matched[0];
		} else {
			$status = $row_element->first('td.status')->text();
			preg_match("~\b([A-Za-z][-,a-z. ']*)+~", $status, $status1_matched);
			$status1 = $status1_matched[0];
			$status2 = '';
		};
		$details_raw = $row_element->first('td.number')->child(1)->getAttribute('href');
		preg_match('~.*(?=\?)~', $details_raw, $details_matched);
		$details_page_url = "https://search.ipaustralia.gov.au{$details_matched[0]}";

		$result[$table_index] = [
			"number" => trim($number),
			"logo_url" => $logo_url,
			"name" => trim($trademark_name),
			"classes" => trim($classes),
			"status1" => trim($status1),
			"status2" => trim($status2),
			"details_page_url" => $details_page_url
		];

		unset($number, $logo_url, $trademark_name, $classes, $status1, $status2, $details_page_url);
	};

	return $result;
}