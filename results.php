<html>
  <body>
    <?php 
      $flight = array();

      $flight["airport1"] = trim($_REQUEST["airport1"]);
      $flight["departureDate"] = trim($_REQUEST["departureDate"]);
      $flight["airport2"] = trim($_REQUEST["airport2"]);
      $flight["returnDate"] = trim($_REQUEST["returnDate"]);
      $flight["currency"] = trim($_REQUEST["currency"]);

      $departure_IATA = substr($flight["airport1"],0,3); 
      $destination_IATA = substr($flight["airport2"],0,3);

      $departure_date_str = (string)$flight["departureDate"];
      $return_date_str = (string)$flight["returnDate"];

      $departure_date_arr = (explode("-",$departure_date_str));
      $return_date_arr = (explode("-",$return_date_str));

      $departure_day = (int)$departure_date_arr[2];
      $departure_month = (int)$departure_date_arr[1];
      $departure_year = (int)$departure_date_arr[0];

      $return_day = (int)$return_date_arr[2];
      $return_month = (int)$return_date_arr[1];
      $return_year = (int)$return_date_arr[0];

      $departure_timestamp = gmmktime(0,0,0,$departure_month,$departure_day,$departure_year);
      $return_timestamp = gmmktime(0,0,0,$return_month,$return_day,$return_year);

      $delta_timestamp = $return_timestamp - $departure_timestamp;
      
      $error = array();

      if ($flight["airport1"] == $flight["airport2"]) { $error["same_city"] = "There is no need to take an airplane to travel within a city."; }

      if ( ($delta_timestamp >= 0)  && (empty($error)) )  {
	
          // check that for both days and months, a "0" is added before the value for any day or month < 10
          if ( $departure_day < 10 ) {$departure_day = "0" . $departure_day;}
          if ( $return_day < 10 ) {$return_day = "0" . $return_day;}

          if ( $departure_month < 10 ) {$departure_month = "0" . $departure_month;}
          if ( $return_month < 10 ) {$return_month = "0" . $return_month;}	
	
          $curlopt_url = 'https://api.travelpayouts.com/v1/prices/cheap?origin=' . $departure_IATA . '&destination=' . $destination_IATA . '&depart_date=' . $departure_year . "-" .  $departure_month . '-' . $departure_day . '&return_date=' . $return_year. "-" . $return_month . "-" . $return_day . "&token=YOUR-API-KEY" . "&currency=" .  $flight["currency"];

          $curl = curl_init();

          curl_setopt_array($curl, array(
		  
            CURLOPT_URL => $curlopt_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
	          CURLOPT_HTTPHEADER => array(
		          "x-access-token: YOUR-API-KEY"
	          ),
	        ));
		
	        $response = curl_exec($curl);
	        $err = curl_error($curl);

	        curl_close($curl);

	        if ($err) {
	          echo "cURL Error #:" . $err;
	        } else {
			
              $my_array  = json_decode($response,true);

			        $flights_count = sizeof($my_array["data"]); 
			
			        if ($flights_count > 0) {

				          $tmp_array = array();
	
				          $sorted_price_ASC_array  = array_column($my_array["data"][$destination_IATA], 'price');

				          array_multisort($sorted_price_ASC_array, SORT_ASC, $my_array["data"][$destination_IATA]);

				          for($x = 0; $x < $flights_count; $x++) {
	
					          if ( ($sorted_price_ASC_array[$x]) == ($my_array["data"][$destination_IATA][$x]["price"]) ) {
						
							$tmp_array[$x]["price"] = $my_array["data"][$destination_IATA][$x]["price"];
							$tmp_array[$x]["airline"] = $my_array["data"][$destination_IATA][$x]["airline"];
							$tmp_array[$x]["flight_number"] = $my_array["data"][$destination_IATA][$x]["flight_number"];
							$tmp_array[$x]["departure_at"] = $my_array["data"][$destination_IATA][$x]["departure_at"];
							$tmp_array[$x]["return_at"] = $my_array["data"][$destination_IATA][$x]["return_at"];			
					          }	
				          }
			        }
		        }
      }		

  ?>

  <div style="margin-left:300px;">

	  <h2 align='center' style='padding:10px'>Cheapest Flights Search Engine</h2>

	  <?php if ($delta_timestamp < 0): ?>
	    <p align='center' style='padding:10px'>The return date cannot occur before the departure date. </p>
		  <p align='center' style='padding:10px'>Please return to the starting page and enter proper values.</p>

	  <?php elseif(isset($error["same_city"])): ?>
		  <p align='center' style='padding:10px'>There is no need to take an airplane to travel within a city.</p> 
		  <p align='center' style='padding:10px'>Please return to the starting page and enter proper values.</p>
		
	  <?php elseif ($flights_count > 0): ?>
	
		  <?php 
		
				echo "<h2 align='center' style='padding:10px'>SELECTED FLIGHT OFFERS</h2>"; 
				echo "<table>
						<thead>
							<tr>
								 <th align='center' style='padding:10px'>FLIGHT NUMBER</th>
								 <th align='center' style='padding:10px'>AIRLINE COMPANY</th>
								 <th align='center' style='padding:10px'>DEPARTURE DATE AND TIME (Z = UTC Time)</th>
								 <th align='center' style='padding:10px'>RETURN DATE AND TIME (Z = UTC Time)</th>
								 <th align='center' style='padding:10px'>PRICE</th>
							</tr>
						</thead>
						<tbody>";				
							foreach ($tmp_array as $flight){
								echo "<tr style='padding:10px'>";
									echo "<td align='center' style='padding:10px'>" . $flight['flight_number'] . "</td>";
									echo "<td align='center' style='padding:10px'>" . $flight['airline'] . "</td>";
									echo "<td align='center' style='padding:10px'>" . $flight['departure_at'] . "</td>";
									echo "<td align='center' style='padding:10px'>" . $flight['return_at'] . "</td>";
									echo "<td align='center' style='padding:10px'>" . $flight['price'] . " ₽</td>";						
								echo "</tr>";
							}
				echo    "</tbody></table>";
	 
		  ?>
	
	  <?php elseif($flights_count == 0): ?>
	
		  <p>Your search did not yield any results.</p> 
		  <p>Please try again later.</p>
	
	  <?php endif; ?>
	
  </div>
  <hr style="margin-right:50px;margin-left:50px;">
  <div style="margin-left:550px;">
	  <h2><b>Kâmi Barut-Wanayo Design © 2020 - 2021</b></h2>
  </div>
 </body>
</html> 









