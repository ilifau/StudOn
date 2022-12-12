<?php

namespace FAU\User\Data;

use FAU\RecordData;

/**
 * Selected data of an ILIAS user
 */
class UserData extends RecordData
{
    protected const tableName = 'usr_data';
    protected const hasSequence = false;
    protected const keyTypes = [
        'usr_id' => 'integer'
    ];
    protected const otherTypes = [
        'login' => 'text',
        'firstname' => 'text',
        'lastname' => 'text',
        'gender' => 'text',
        'email' => 'text',
        'matriculation' => 'text'
    ];


    protected int $usr_id;
    protected string $login;
    protected string $firstname;
    protected string $lastname;
    protected ?string $gender;
    protected ?string $mail;
    protected ?string $matriculation;

    protected ?Person $person = null;

    /** @var Education[]  */
    protected array $educations = [];


    public function __construct (
        int $usr_id,
        string $login,
        string $firstname,
        string $lastname,
        ?string $gender,
        ?string $email,
        ?string $matriculation
    ) {
        $this->usr_id = $usr_id;
        $this->login = $login;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->gender = $gender;
        $this->email = $email;
        $this->matriculation = $matriculation;
    }

    public static function model()
    {
        return new self(0, '', '', '', null, null, null, null);
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->usr_id;
    }

    /**
     * @return string
     */
    public function getLogin() : string
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getFirstname() : string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname() : string
    {
        return $this->lastname;
    }

    /**
     * @return string|null
     */
    public function getGender() : ?string
    {
        return $this->gender;
    }

    /**
     * @return string|null
     */
    public function getEmail() : ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getMatriculation() : ?string
    {
        return $this->matriculation;
    }

    /**
     * @return Person|null
     */
    public function getPerson() : ?Person
    {
        return $this->person;
    }

    /**
     * @return Education[]
     */
    public function getEducations() : array
    {
        return $this->educations;
    }

    /**
     * @param Person|null $person
     * @return self
     */
    public function withPerson(?Person $person) : self
    {
        $clone = clone $this;
        $clone->person = $person;
        return $clone;
    }

    /**
     * @param Education $education
     * @return self
     */
    public function withEducation(Education $education) : self
    {
        $clone = clone $this;
        $clone->educations[] = $education;
        return $clone;
    }
}