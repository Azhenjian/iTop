<?php
/**
 * Copyright (C) 2013-2020 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */


/**
 * ormDocument
 * encapsulate the behavior of a binary data set that will be stored an attribute of class AttributeBlob 
 *
 * @package     itopORM
 */
class ormDocument
{
	const DEFAULT_DOWNLOADS_COUNT = 0;

	protected $m_data;
	protected $m_sMimeType;
	protected $m_sFileName;
	/** @var int $m_iDownloadsCount */
	private $m_iDownloadsCount;

	/**
	 * Constructor
	 *
	 * @param null $data
	 * @param string $sMimeType
	 * @param string $sFileName
	 * @param int $iDownloadsCount
	 */
	public function __construct($data = null, $sMimeType = 'text/plain', $sFileName = '', $iDownloadsCount = self::DEFAULT_DOWNLOADS_COUNT)
	{
		$this->m_data = $data;
		$this->m_sMimeType = $sMimeType;
		$this->m_sFileName = $sFileName;
		$this->m_iDownloadsCount = $iDownloadsCount;
	}

	public function __toString()
	{
	    if($this->IsEmpty()) return '';

		return MyHelpers::beautifulstr($this->m_data, 100, true);
	}

	public function IsEmpty()
	{
		return ($this->m_data == null);
	}
	
	public function GetMimeType()
	{
		return $this->m_sMimeType;
	}
	public function GetMainMimeType()
	{
		$iSeparatorPos = strpos($this->m_sMimeType, '/');
		if ($iSeparatorPos > 0)
		{
			return substr($this->m_sMimeType, 0, $iSeparatorPos);
		}
		return $this->m_sMimeType;
	}

	/**
	 * @return int size in bits
	 * @uses strlen which returns the no of bits used
	 * @since 2.7.0
	 */
	public function GetSize()
	{
		return strlen($this->m_data);
	}

	/**
	 * @param int $precision
	 *
	 * @return string
	 * @uses utils::BytesToFriendlyFormat()
	 */
	public function GetFormattedSize($precision = 2)
	{
		$bytes = $this->GetSize();
		return utils::BytesToFriendlyFormat($bytes, $precision);
	}
	public function GetData()
	{
		return $this->m_data;
	}

	public function GetFileName()
	{
		return $this->m_sFileName;
	}

	/**
	 * Return the number of times the document has been downloaded (through the standard API!)
	 *
	 * @see static::DownloadDocument()
	 * @return int
	 * @since XXX
	 */
	public function GetDownloadsCount()
	{
		// Force cast to get 0 instead of null on fields prior to the features that have never been downloaded.
		return (int) $this->m_iDownloadsCount;
	}

	/**
	 * Increase the number of downloads of the document by $iNumber
	 *
	 * @param int $iNumber
	 * @since XXX
	 */
	public function IncreaseDownloadsCount($iNumber = 1)
	{
		$this->m_iDownloadsCount += $iNumber;
	}

	public function GetAsHTML()
	{
		$sResult = '';
		if ($this->IsEmpty())
		{
			// If the filename is not empty, display it, this is used
			// by the creation wizard while the file has not yet been uploaded
			$sResult = htmlentities($this->GetFileName(), ENT_QUOTES, 'UTF-8');
		}
		else
		{
			$data = $this->GetData();
			$sResult = htmlentities($this->GetFileName(), ENT_QUOTES, 'UTF-8').' [ '.$this->GetMimeType().', size: '.strlen($data).' byte(s) ]<br/>';
		}
		return $sResult;
	}
		
	/**
	 * Returns an hyperlink to display the document *inline*
	 * @return string
	 */	 	 	
	public function GetDisplayLink($sClass, $Id, $sAttCode)
	{
		return "<a href=\"".utils::GetAbsoluteUrlAppRoot()."pages/ajax.render.php?operation=display_document&class=$sClass&id=$Id&field=$sAttCode\" target=\"_blank\" >".htmlentities($this->GetFileName(), ENT_QUOTES, 'UTF-8')."</a>\n";
	}
	
	/**
	 * Returns an hyperlink to download the document (content-disposition: attachment)
	 * @return string
	 */	 	 	
	public function GetDownloadLink($sClass, $Id, $sAttCode)
	{
		return "<a href=\"".utils::GetAbsoluteUrlAppRoot()."pages/ajax.document.php?operation=download_document&class=$sClass&id=$Id&field=$sAttCode\">".htmlentities($this->GetFileName(), ENT_QUOTES, 'UTF-8')."</a>\n";
	}

	/**
	 * Returns an URL to display a document like an image
	 * @return string
	 */
	public function GetDisplayURL($sClass, $Id, $sAttCode)
	{
		// TODO: When refactoring this with the URLMaker system, mind to also change calls in the portal (look for the "p_object_document_display" route)
		return utils::GetAbsoluteUrlAppRoot() . "pages/ajax.render.php?operation=display_document&class=$sClass&id=$Id&field=$sAttCode";
	}

	/**
	 * Returns an URL to download a document like an image (uses HTTP caching)
	 * @return string
	 */
	public function GetDownloadURL($sClass, $Id, $sAttCode)
	{
		// Compute a signature to reset the cache anytime the data changes (this is acceptable if used only with icon files)
		$sSignature = md5($this->GetData());
		// TODO: When refactoring this with the URLMaker system, mind to also change calls in the portal (look for the "p_object_document_display" route)
		return utils::GetAbsoluteUrlAppRoot() . "pages/ajax.document.php?operation=download_document&class=$sClass&id=$Id&field=$sAttCode&s=$sSignature&cache=86400";
	}

	public function IsPreviewAvailable()
	{
		$bRet = false;
		switch($this->GetMimeType())
		{
			case 'image/png':
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/gif':
			$bRet = true;
			break;
		}
		return $bRet;
	}

	/**
	 * Downloads a document to the browser, either as 'inline' or 'attachment'
	 *
	 * @param WebPage $oPage The web page for the output
	 * @param string $sClass Class name of the object
	 * @param mixed $id Identifier of the object
	 * @param string $sAttCode Name of the attribute containing the document to download
	 * @param string $sContentDisposition Either 'inline' or 'attachment'
	 * @param string $sSecretField The attcode of the field containing a "secret" to be provided in order to retrieve the file
	 * @param string $sSecretValue The value of the secret to be compared with the value of the attribute $sSecretField
	 *
	 * @return void
	 */
	public static function DownloadDocument(WebPage $oPage, $sClass, $id, $sAttCode, $sContentDisposition = 'attachment', $sSecretField = null, $sSecretValue = null)
	{
		try
		{
			$oObj = MetaModel::GetObject($sClass, $id, false, false);
			if (!is_object($oObj))
			{
				throw new Exception("Invalid id ($id) for class '$sClass' - the object does not exist or you are not allowed to view it");
			}
			if (($sSecretField != null) && ($oObj->Get($sSecretField) != $sSecretValue))
			{
				usleep(200);
				throw new Exception("Invalid secret for class '$sClass' - the object does not exist or you are not allowed to view it");
			}
			/** @var \ormDocument $oDocument */
			$oDocument = $oObj->Get($sAttCode);
			if (is_object($oDocument))
			{
				$oPage->TrashUnexpectedOutput();
				$oPage->SetContentType($oDocument->GetMimeType());
				$oPage->SetContentDisposition($sContentDisposition,$oDocument->GetFileName());
				$oPage->add($oDocument->GetData());

				// Update downloads count only when content disposition is set to "attachment" as other disposition are to display the document within the page
				if($sContentDisposition === 'attachment')
				{
					$oDocument->IncreaseDownloadsCount();
					$oObj->Set($sAttCode, $oDocument);
					$oObj->DBUpdate();
				}
			}
		}
		catch(Exception $e)
		{
			$oPage->p($e->getMessage());
		}
	}
}
