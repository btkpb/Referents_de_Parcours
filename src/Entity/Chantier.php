<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: 'chantier')]
class Chantier { #[ORM\Id,ORM\GeneratedValue,ORM\Column] private ?int $id=null; #[ORM\Column(length:160)] private string $nom=''; #[ORM\Column(length:180)] private string $localisation=''; #[ORM\Column(length:160,nullable:true)] private ?string $entreprise=null; #[ORM\Column(length:40)] private string $statut='ACTIF'; public function getId():?int{return $this->id;} public function getNom():string{return $this->nom;} public function setNom(string $v):self{$this->nom=$v;return $this;} public function getLocalisation():string{return $this->localisation;} public function setLocalisation(string $v):self{$this->localisation=$v;return $this;} public function getEntreprise():?string{return $this->entreprise;} public function setEntreprise(?string $v):self{$this->entreprise=$v;return $this;} public function getStatut():string{return $this->statut;} public function setStatut(string $v):self{$this->statut=$v;return $this;} }
