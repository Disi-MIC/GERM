<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-superadmin',
    description: 'Crée (ou met à jour) un compte superadmin GERM.',
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $io->ask('Email du superadmin', 'superadmin@mincom.gouv');
        $nom = $io->ask('Nom', 'Admin');
        $prenom = $io->ask('Prénom', 'Super');
        $password = $io->askHidden('Mot de passe (min. 8 caractères)');

        if (!$password || strlen($password) < 8) {
            $io->error('Le mot de passe doit contenir au moins 8 caractères.');

            return Command::FAILURE;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]) ?? new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRoles([User::ROLE_SUPERADMIN]);
        $user->setActif(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Compte superadmin "%s" créé/mis à jour avec succès.', $email));

        return Command::SUCCESS;
    }
}
