<?php
class Sro_model extends CI_Model {

	public function __construct()
	{
		$this->load->database();
	}


	public function get_items($eprintid = "10000")
	{
		
		$query = $this->db->query('Select eprintid, title, concat(e.datestamp_year,"/",e.datestamp_month,"/",e.datestamp_day) AS livedate, date_year, publication, id_number, ispublished
			from eprint e
			where e.eprintid = "10000"
			AND eprint_status = "archive"
			AND type = "article"
			ORDER BY e.datestamp_year desc, e.datestamp_month
			');
		return $query->row_array();
	}
	
	public function get_nojournaltitle()
	{
			$query = $this->db->query('Select eprintid, title, concat(e.datestamp_year,"/",e.datestamp_month,"/",e.datestamp_day) AS livedate, date_year, publication, id_number, ispublished, e.issn
			from eprint e
			where (publication is null or publication = "")
			AND eprint_status = "archive"
			AND type = "article"
			ORDER BY e.datestamp_year desc, e.datestamp_month
			');
			return $query->result_array();
	}
	

	public function get_notsetaspublished($months="0")
	{
		// find out the year we want, based on number of months we are going back
		$getyear = date('Y', strtotime("-$months month"));
		// find out the month we want based on the number of months we are going back
		$getmonth = date('m', strtotime("-$months month"));
		// so if 12 and today is 1/sept/2014 the get stuff from BEFORE 2013
		// OR ON or BEFORE 2013 AND ON or BEFORE 9
		$query = $this->db->query('
			select e.eprintid, concat(e.datestamp_year,"/",e.datestamp_month,"/",e.datestamp_day) AS livedate, e.title, e.ispublished, e.type, e.date_year as pubyear, e.date_month as pubmonth, e.publication,
			e.id_number, e.volume, e.number
			from eprint e
			where e.ispublished != "pub"
			and e.eprintid > 10000
			and e.eprint_status = "archive"
			and (e.datestamp_year < ' . $getyear . '
				OR e.datestamp_year <= ' . $getyear . ' AND e.datestamp_month <= ' . $getmonth . ')
			and e.`type` != "thesis"
			order by e.type, e.datestamp_year desc, e.datestamp_month desc, e.datestamp_day desc
		');
		return $query->result_array();
		
		
	}
	
	public function get_total()
	{
		return $this->db->select('count(*) as total')
                        ->from('eprint')
                        ->where('eprint_status', "archive")
                        ->get()
                        ->result();
	}
	
	public function get_oatotal_bytype()
	{
		return $this->db->select('count(*) as total, e.type')
						->from('document f')
						->join('eprint e', 'e.eprintid = f.eprintid')
						->where('e.eprint_status', "archive")
						->where('format like "application%"')
						->where('security', 'public')
						->group_by('type with rollup')
						->get()
                        ->result();
	}
	
	// need to work out current year
	public function get_newrecords_bymonth()
	{
		return $this->db->select('count(*) as total, concat(datestamp_month,"/",datestamp_year) as "monthadded"', FALSE)
						->from('eprint')
						->where('eprint_status', 'archive')
						->where('datestamp_year', '2014')
						->group_by('datestamp_month with rollup')
						->get()
                        ->result();
	}
	
	public function get_oalist($field, $value)
	{
		return $this->db->select('e.eprintid, concat(datestamp_year, "/", datestamp_month, "/", datestamp_day) as datelive, e.title, e.type, e.date_year', FALSE)
					->from('document f')
					->join('eprint e' , 'e.eprintid = f.eprintid')
					->where('e.eprint_status', "archive")
					->like('f.format', 'application', 'after')
					->where('f.security', 'public')
					->where($field,$value)
					->group_by('f.eprintid')
					->order_by('datelive')
					->get()
                    ->result();
	}
	
	public function get_schools_year()
	{
		// we're returning more than one query, so chuck it all in an array
		$schoolsarray=array();
		// first get total records for each School
		$query = $this->db->select('COUNT( * ) AS  "total", t.name_name as "school", t.subjectid as "schoolid"', FALSE)
					->from('eprint e')
					->join('eprint_divisions d' , 'e.eprintid = d.eprintid')
					->join('subject_ancestors a' , 'd.divisions = a.subjectid')
					->join('subject_name_name t' , 'a.ancestors = t.subjectid')
					->where('e.eprint_status', "archive")
					->where('a.pos', '1')
					->group_by('t.name_name')
					->order_by('t.name_name')
					->get();
		foreach ($query->result() as $row) {
			// add results to multi-dem array. use schoolid as id 
			$schoolsarray["$row->schoolid"]["schoolid"] = "$row->schoolid";
			$schoolsarray["$row->schoolid"]["schoolname"] = "$row->school";
			$schoolsarray["$row->schoolid"]["schooltotalrecords"] = "$row->total";
		}
		
		// now add oa totals to each school
		$query = $this->db->select('count(*) as "total", t.name_name as "school", t.subjectid as "schoolid"', FALSE)
					->from('document f')
					->join('eprint e' , 'e.eprintid = f.eprintid')
					->join('eprint_divisions d' , 'e.eprintid = d.eprintid')
					->join('subject_ancestors a' , 'd.divisions = a.subjectid')
					->join('subject_name_name t' , 'a.ancestors = t.subjectid')
					->where('e.eprint_status', "archive")
					->like('f.format', 'application', 'after')
					->where('f.security', 'public')
					->where('a.pos', '1')
					->where('t.subjectid !=', 'd328')
					->group_by('t.subjectid')
					->get();
		foreach ($query->result() as $row) {
			$schoolsarray["$row->schoolid"]["schooloatotal"] = "$row->total";
		}
		
		return $schoolsarray;
					
	}
	
	public function get_school_summary($school)
	{
		$schoolarray=array();
		$query = $this->db->select('COUNT( * ) AS  "total", t.name_name as "school", t.subjectid as "schoolid"', FALSE)
					->from('eprint e')
					->join('eprint_divisions d' , 'e.eprintid = d.eprintid')
					->join('subject_ancestors a' , 'd.divisions = a.subjectid')
					->join('subject_name_name t' , 'a.ancestors = t.subjectid')
					->where('e.eprint_status', "archive")
					->where('a.pos', '1')
					->where('t.subjectid', $school)
					->group_by('t.name_name')
					->order_by('t.name_name')
					->get();
		foreach ($query->result() as $row) {
			$schoolarray["schoolid"] = "$row->schoolid";
			$schoolarray["schoolname"] = "$row->school";
			$schoolarray["totalrecords"] = "$row->total";
		}
		// now get oa data.
		$query = $this->db->select('count(*) as "total", t.name_name as "school", t.subjectid as "schoolid"', FALSE)
					->from('document f')
					->join('eprint e' , 'e.eprintid = f.eprintid')
					->join('eprint_divisions d' , 'e.eprintid = d.eprintid')
					->join('subject_ancestors a' , 'd.divisions = a.subjectid')
					->join('subject_name_name t' , 'a.ancestors = t.subjectid')
					->where('e.eprint_status', "archive")
					->like('f.format', 'application', 'after')
					->where('f.security', 'public')
					->where('a.pos', '1')
					->where('t.subjectid', $school)
					->group_by('t.subjectid')
					->get();
		foreach ($query->result() as $row) {
			$schoolarray["oatotal"] = "$row->total";
		}
		return $schoolarray;
		
	}
	
	public function get_recentoaitems()
	{
		$query = $this->db->query('select e.eprintid, concat(e.datestamp_year,"/",e.datestamp_month,"/",e.datestamp_day) AS livedate, 
		date_year, e.oa_status, e.oa_embargo_length, e.oa_licence_type, 
		group_concat(DISTINCT ff.funder_information_funder SEPARATOR ", and ") As funder,  
			e.title, e.publisher, e.ispublished, e.`type`, e.id_number,
			group_concat(DISTINCT n.creators_name_given, " ", n.creators_name_family SEPARATOR ", ") as authors
			from eprint e
			left outer join eprint_funder_information_funder ff on ff.eprintid = e.eprintid
			left outer join eprint_funder_information_funder_ref fr on (fr.eprintid = ff.eprintid AND fr.pos = ff.pos)
			JOIN eprint_creators_id i on e.eprintid = i.eprintid
 			JOIN eprint_creators_name n on n.eprintid = i.eprintid AND n.pos = i.pos
			Where (e.oa_status is not null
				OR e.oa_embargo_length is not null
				OR e.oa_licence_type is not null)
			AND e.eprint_status = "archive"
			and i.creators_id is not null
			GROUP BY e.eprintid
			ORDER BY e.datestamp_year desc, e.datestamp_month desc, e.datestamp_day desc
		');
		return $query->result_array();
	}
	
	public function get_recentfunderitems()
	{
		$query = $this->db->query('select e.eprintid, concat(e.datestamp_year,"/",e.datestamp_month,"/",e.datestamp_day) AS 	livedate, 
			date_year, e.oa_status, e.oa_embargo_length, e.oa_licence_type, 
			group_concat(DISTINCT ff.funder_information_funder SEPARATOR ", and ") As funder,
			group_concat(DISTINCT fr.funder_information_funder_ref SEPARATOR ", and ") As funderref,
			group_concat(DISTINCT fp.funder_information_project_name SEPARATOR ", and ") As projectname,
			group_concat(DISTINCT fn.funder_information_project_number SEPARATOR ", and ") As projectnum,
			e.title, e.publisher, e.ispublished, e.`type`, e.id_number,
			group_concat(DISTINCT n.creators_name_given, " ", n.creators_name_family SEPARATOR ", ") as authors
			from eprint e
			left outer join eprint_funder_information_funder ff on ff.eprintid = e.eprintid
			left outer join eprint_funder_information_funder_ref fr on (fr.eprintid = ff.eprintid AND fr.pos = ff.pos)
			left outer join eprint_funder_information_project_name fp on (fp.eprintid = ff.eprintid AND fp.pos = ff.pos)
			left outer join eprint_funder_information_project_number fn on (fn.eprintid = ff.eprintid AND fn.pos = ff.pos)
			JOIN eprint_creators_id i on e.eprintid = i.eprintid
 			JOIN eprint_creators_name n on n.eprintid = i.eprintid AND n.pos = i.pos
			Where (ff.funder_information_funder is not null
				OR fr.funder_information_funder_ref is not null
				OR fp.funder_information_project_name is not null
				OR fn.funder_information_project_number is not null
				)
			AND e.eprint_status = "archive"
			and i.creators_id is not null
			GROUP BY e.eprintid
			ORDER BY e.datestamp_year desc, e.datestamp_month desc, e.datestamp_day desc
		');
		return $query->result_array();
	}

}