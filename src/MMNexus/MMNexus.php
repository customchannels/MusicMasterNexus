<?php
/*
 * MusicMaster Nexus Server API
 * Created by: Kurt Oleson
 */

namespace App;

use Vinelab\Http\Client as HttpClient;

class MMNexus 
{
	protected $server_address;

	protected $station;

	protected $HttpClient;

	public function __construct($address, $station = 'Enterprise') 
	{
		$this->server_address = $address;
		$this->station = $station;
		$this->HttpClient = new HttpClient;
	}

	public function getFieldList()
	{
		$field_list = array();

		$request = '<mmRequest command="getFieldList" station='.$this->station.' />';

		$response = $this->HttpClient->post(['url' => $this->server_address, 'content' => $request]);

		$xml = simplexml_load_string($response->content());
		$json = json_encode($xml);
		$result = json_decode($json,TRUE);

		foreach ($result['contents']['fields']['field'] as $field) {
			array_push($field_list, $field['@attributes']['name']);
		}
		
		return $field_list;
	}

	public function getSongId($song)
	{
		$song_id = NULL;

		$result = $this->songSearch($song);

		if ($result['contents']['songList']['@attributes']['recordCount'] !== "0") 
		{
			$song_id = $result['contents']['songList']['song']['@attributes']['songId'];
		}

		return $song_id;
	}

	public function songSearch($song)
	{
		$song_id = '';

		$request = '<mmRequest command="getSongsByQuery" station="'.$this->station.'">
		               <contents>
		                   <query>
		                      <filters>
		                         <filter target="File Name" operator="contains" value="'.$song.'"/>
		                      </filters>
		                   </query>
		               </contents>
		            </mmRequest>';

		$response = $this->HttpClient->post(['url' => $this->server_address, 'content' => $request]);

		$xml = simplexml_load_string($response->content());
		$json = json_encode($xml);
		$result = json_decode($json,TRUE);

		return $result;
	}

	public function artistAlbumSearch($artist, $album)
	{
		$request = '<mmRequest command="getSongsByQuery" station="'.$this->station.'">
   						<contents>
       						<query>
          						<filters>
             						<filter target="Artist" operator="contains" value="'.$artist.'"/>
          						</filters>
       						</query>
       						<fields>
         	 					<field name="Album" />
       						</fields>
   						</contents>
					</mmRequest>';

		$response = $this->HttpClient->post(['url' => $this->server_address, 'content' => $request]);

		$xml = simplexml_load_string($response->content());
		$json = json_encode($xml);
		$result = json_decode($json,TRUE);

		if ($result['contents']['songList']['@attributes']['recordCount'] == "0") {

			return false;

		} elseif (intval($result['contents']['songList']['@attributes']['recordCount']) == 1) {
			if ($result['contents']['songList']['song']['field'] == $album) {
				echo 'Album already exists by same artist'.PHP_EOL;
				return true;
			}
		} else {
			foreach ($result['contents']['songList']['song'] as $song) 
			{
				if ($song['field'] == $album)
				{
					echo 'Album already exists by same artist'.PHP_EOL;
					return true;
				}
			}
		}

		return false;
	}

	public function updateSongInfo($song, $field, $value)
	{
		$song_id = $this->getSongId($song);

		$request = '<mmRequest command="updateSongs" station="'.$this->station.'">
					   <contents>
					       <songList>
					          <song songId="'.$song_id.'" >
					             <field name="'.$field.'">'.$value.'</field>
					          </song>
					       </songList>
					   </contents>
					</mmRequest>';

		// echo $request;
		$response = $this->HttpClient->post(['url' => $this->server_address, 'content' => $request]);

		$xml = simplexml_load_string($response->content());
		$json = json_encode($xml);
		$result = json_decode($json,TRUE);

		// echo var_dump($result);
		if ($result['@attributes']['status'] !== 'ok') {
			return false;
		} else {
			return true;
		}
	}

	public function uploadSongInfo($song_info)
	{
		$search = $this->songSearch($song_info['name']);
		// echo var_dump($search);

		if ($search['contents']['songList']['@attributes']['recordCount'] !== "0") 
		{
			echo 'Song Already in MusicMaster'.PHP_EOL;

		} else {

			$request = '<mmRequest command="importSongs" station="'.$this->station.'">
			               <contents>
			                   <songList>
			                      <song>
			                         <field name="Category">NEW</field>
			                         <field name="File Path">M:\</field>
			                         <field name="Xen Path">M:\Music\</field>
			                         <field name="File Name">'.$song_info['name'].'.mp3</field>
			                         <field name="Artist">'.$song_info['artist'].'</field>
			                         <field name="Title">'.$song_info['title'].'</field>
			                         <field name="Album">'.$song_info['album'].'</field>
			                         <field name="Peak Year">'.$song_info['year'].'</field>
			                         <field name="Label">'.$song_info['publisher'].'</field>
			                         <field name="Run Time">'.$song_info['length'].'</field>
			                         <field name="BitRate">'.$song_info['bitrate'].'</field>
			                      </song>
			                   </songList>
			               </contents>
			            </mmRequest>';

			$response = $this->HttpClient->post(['url' => $this->server_address, 'content' => $request]);

			$xml = simplexml_load_string($response->content());
			$json = json_encode($xml);
			$result = json_decode($json,TRUE);

			if ($result['@attributes']['status'] !== 'ok') {

				return false;
			}
		}
			return true;
	}
}
