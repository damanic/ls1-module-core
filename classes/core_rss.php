<?php

	class Core_Rss
	{
		private $_Title;
		private $_AltUrl;
		private $_Description;
		private $_Entries;
		private $_RssLink;

		public function __construct( $Title, $AltUrl, $Description, $RssLink )
		{
			$this->_Title = $Title;
			$this->_AltUrl = $AltUrl;
			$this->_Description = $Description;
			$this->_Entries = array();
			$this->_RssLink = $RssLink;
		}

		/**
		 * Adds an entry to the channel
		 */
		public function add_entry( $Title, $Link, $Id, $UpdateDate, $Summary, $CreateDate, $Author, $Body )
		{
			$Entry = array();

			$Entry['Title'] = $Title;
			$Entry['Link'] = $Link;
			$Entry['UpdateDate'] = $UpdateDate;
			$Entry['Id'] = $Id;
			$Entry['Summary'] = $Summary;
			$Entry['CreateDate'] = $CreateDate;
			$Entry['Author'] = $Author;
			$Entry['Body'] = $Body;

			$this->_Entries[] = (object)$Entry;
		}

		/**
		 * Returns XML string representing the channel
		 */
		public function to_xml()
		{
			$Result = null;
			$GmtFormat = '%a, %d %b %Y %H:%M:%S GMT';
			$GmtNow = Phpr_DateTime::gmtNow()->format( $GmtFormat );

			$Result .= '<?xml version="1.0" encoding="UTF-8"?>';
			$Result .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'."\n";
			$Result .= '<atom:link href="'.$this->_RssLink.'" rel="self" type="application/rss+xml" />'."\n";
			$Result .= '<title>'.self::cdata_wrap($this->_Title).'</title>'."\n";
			$Result .= '<link>'.$this->_AltUrl."</link>\n";
			$Result .= '<description>'.self::cdata_wrap($this->_Description).'</description>'."\n";
			$Result .= '<pubDate>'.$GmtNow.'</pubDate>'."\n";
			$Result .= '<lastBuildDate>'.$GmtNow.'</lastBuildDate>'."\n";
			$Result .= '<generator>LemonStand</generator>'."\n";
			
			foreach ($this->_Entries as $Entry)
			{
				$Result .= '<item>'."\n";
				$Result .= '<title>'.self::cdata_wrap($Entry->Title).'</title>'."\n";
				$Result .= '<link>'.$Entry->Link.'</link>'."\n";
				$Result .= '<guid>'.$Entry->Link.'</guid>'."\n";
				$Result .= '<pubDate>'.$Entry->CreateDate->format($GmtFormat).'</pubDate>'."\n";
				$Result .= '<description>'.self::cdata_wrap($Entry->Body).'</description>'."\n";
//				$Result .= '<content type="text/html" xml:lang="en-US"><![CDATA['.$Entry->Body.']]></content>'."\n";
				$Result .= '</item>'."\n";
			}

			$Result .= '</channel></rss>';
			return $Result;
		}
		
		public static function cdata_wrap($value)
		{
			$value = str_replace(']]>', ']]&gt;', $value);
			return '<![CDATA['.$value.']]>';
		}
	}
?>