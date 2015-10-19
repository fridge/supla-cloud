<?php
/*
 src/AppBundle/Model/AccessIdManager.php

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace AppBundle\Model;

use AppBundle\Entity\AccessID;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Util\SecureRandom;

/**
 * @author Przemyslaw Zygmunt p.zygmunt@acsoftware.pl [AC SOFTWARE]
 */
class AccessIdManager 
{	
	protected $translator;
	protected $doctrine;	
	protected $rep;
	protected $sec;
	
	public function __construct($translator, $doctrine, $security_context)
	{
		$this->translator = $translator;
		$this->doctrine = $doctrine;
		$this->rep = $doctrine->getRepository('AppBundle:AccessID');
		$this->sec = $security_context;
	}
	
	public function anyIdExists(User $user) 
	{		
		return $user !== null && ( $user->getAccessIDS()->count() > 0 || $this->rep->findOneBy(array('user' => $user)) !== null );
	}
	
	public function totalCount(User $user)
	{
		$qb = $this->rep->createQueryBuilder('a');
	
		return intval($qb->select('count(a.id)')
				->where($qb->expr()->eq('a.user', ':user'))
				->setParameter('user', $user->getId())
				->getQuery()
				->getSingleScalarResult());
	}
	
	public function createID(User $user, $ifnotexists = false) 
	{
		if ( $ifnotexists === false
			 || $this->anyIdExists($user) === false ) {
					
			 $aid = new AccessID($user);
		
			 if ( $aid !== null ) {
			 	$generator = new SecureRandom();
			 	$aid->setPassword(bin2hex($generator->nextBytes(4)));
			 	$aid->setCaption($this->translator->trans('Access identifier')." #".($this->totalCount($user)+1));
			 	
			 	return $aid;
			 }
		}
		
		return null;
	}
	
	public function accessIdById($id)
	{
		$user = $this->sec->getToken()->getUser();
	
		if ( $user === null )
			return null;
	

		return $this->rep->findOneBy(array('user' => $user, 'id' => $id));
	}
}