<?

class B24Custom extends B24Common
{
	public static function getContactFields()
	{
		$result = static::request('crm.contact.fields', 'POST', array());

		return $result;
	}

	public static function getLeadFields()
	{
		$result = static::request('crm.lead.fields', 'POST', array());

		return $result;
	}

	public static function getCompanyFields()
	{
		$result = static::request('crm.company.fields', 'POST', array());

		return $result;
	}

	public static function getListContacts(array $select, $start = 0)
	{
		$data = static::request('crm.contact.list.json', 'POST', array(
			"start" => $start,
			"select" => $select,
		));

		return $data;
	}

	public static function getListLeads(array $select, $start = 0)
	{
		$data = static::request('crm.lead.list.json', 'POST', array(
			"start" => $start,
			"select" => $select,
		));

		return $data;
	}

	public static function getListCompanies(array $select, $start = 0)
	{
		$data = static::request('crm.company.list.json', 'POST', array(
			"start" => $start,
			"select" => $select,
		));

		return $data;
	}


	public static function getAllContacts($select)
	{
		$start = 0;
		$listContacts = array();

		do
		{
			$contacts = self::getListContacts($select, $start);

			if ($contacts['result'])
			{
				foreach ($contacts['result'] as $contact)
				{
					$listContacts[] = $contact;
				}
			}

			if ($contacts['next'])
			{
				$start = $contacts['next'];
			}
			else
			{
				$start = 0;
			}

		} while ($start);

		return $listContacts;
	}

	public static function getAllLeads($select)
	{
		$start = 0;
		$listLeads = array();

		do
		{
			$leads = self::getListLeads($select, $start);

			if ($leads['result'])
			{
				foreach ($leads['result'] as $lead)
				{
					$listLeads[] = $lead;
				}
			}

			if ($leads['next'])
			{
				$start = $leads['next'];
			}
			else
			{
				$start = 0;
			}

		} while ($start);

		return $listLeads;
	}

	public static function getAllCompanies($select)
	{
		$start = 0;
		$listCompanies = array();

		do
		{
			$companies = self::getListCompanies($select, $start);

			if ($companies['result'])
			{
				foreach ($companies['result'] as $company)
				{
					$listCompanies[] = $company;
				}
			}

			if ($companies['next'])
			{
				$start = $companies['next'];
			}
			else
			{
				$start = 0;
			}

		} while ($start);

		return $listCompanies;
	}


	public static function addContact($data)
	{
		$contactId = '';

		$data = static::request(
			'crm.contact.add',
			'POST',
			array(
				"fields" => $data,
			)
		);

		if($data['result'])
		{
			$contactId = $data['result'];
		}

		return $contactId;
	}

	public static function addCompany($data)
	{
		$companyId = '';

		$data = static::request(
			'crm.company.add',
			'POST',
			array(
				"fields" => $data,
			)
		);

		if($data['result'])
		{
			$companyId = $data['result'];
		}

		return $companyId;
	}

	/**
	 * The method for update contact
	 * @param integer $id
	 * @param array $updateData
	 */
	public static function updateContact($id, $updateData)
	{
		static::request(
			'crm.contact.update',
			'POST',
			array(
				"id" => $id,
				"fields" => $updateData
			)
		);
	}

	/**
	 * The method for update company
	 * @param integer $id
	 * @param array $updateData
	 */
	public static function updateCompany($id, $updateData)
	{
		static::request(
			'crm.company.update',
			'POST',
			array(
				"id" => $id,
				"fields" => $updateData
			)
		);
	}

	public static function getContactByID($ID) {
		$result = array();

		$data = static::request(
			'crm.contact.get.json',
			'POST',
			array(
				"id" => $ID
			)
		);

		if ($data['result'])
		{
			$result = $data['result'];
		}

		return $result;
	}

	public static function getCompanyByID($ID) {
		$result = array();

		$data = static::request(
			'crm.company.get.json',
			'POST',
			array(
				"id" => $ID
			)
		);

		if ($data['result'])
		{
			$result = $data['result'];
		}

		return $result;
	}
}