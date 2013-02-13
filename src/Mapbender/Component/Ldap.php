<?php
    namespace Mapbender\Component;
    
	/**
	 * Copyright (C) 2011 Wheregroup
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2, or (at your option)
	 * any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
	 *
	 * v0.2
	 */

	class Ldap {
		private $conn;
		private $error = null;
		private $protocol_version;
		
		public function __construct($server, $port, $protocol_version) {
            $this->protocol_version = $protocol_version;
			
			$this->conn = ldap_connect($server, $port);
			if($this->conn) {
				return true;
			}
			
			$this->error = "The LDAP server is not reachable.";
			return false;
		}
		
		public function bind($username = null, $password = null) {
			if(!$this->conn) return false;
			
			ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, $this->protocol_version);
			
			if(ldap_bind($this->conn, $username, $password)) {
				 
				return true;
			}
			
			$this->error = "Cannot bind to the LDAP server.";
			return false;
		}
		
		public function search($base_dn, $filter) {
			if(!$this->conn) return false;

			return ldap_get_entries(
                $this->conn, 
                ldap_search($this->conn, $base_dn, $filter)
            );
		}
		
		
		public function lastError($return = false) {
			if($return) { 
				return $this->error;
			}
			echo $this->error;
		}
		
		public function hasError() {
			if(is_null($this->error)) {
				return false;
			}
			return true;
		}
	}
