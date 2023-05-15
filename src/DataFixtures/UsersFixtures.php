<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Users;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private SluggerInterface $slugger
    ){}

    public function load(ObjectManager $manager): void
    {
        $admin = new Users();
        $admin->setEmail("camille.mathien@gmail.com");
        $admin->setLastname("Mathien");
        $admin->setFirstname("Camille");
        $admin->setAddress("28 rue Rémy Cogghe");
        $admin->setZipcode("59100");
        $admin->setCity("Roubaix");
        $admin->setPassword(
            $this->passwordEncoder->hashPassword($admin, 'password')
        );
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $faker = Factory::create('fr_FR');

        for($usr = 1; $usr <= 5; $usr++){
            $user = new Users();
            $user->setEmail($faker->email());
            $user->setLastname($faker->lastName());
            $user->setFirstname($faker->firstName());
            $user->setAddress($faker->streetAddress());
            $user->setZipcode(str_replace(' ', '', $faker->postcode()));
            $user->setCity($faker->city());
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, 'password')
            );
            $manager->persist($user);
        }

        $manager->flush();
    }
}
