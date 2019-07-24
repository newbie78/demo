<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190724162059 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE symfony_tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL, UNIQUE INDEX UNIQ_69D7457E5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE symfony_post (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(191) NOT NULL, slug VARCHAR(191) NOT NULL, summary LONGTEXT NOT NULL, content LONGTEXT NOT NULL, published_at DATETIME NOT NULL, INDEX IDX_99ECBCDEF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE symfony_demo_post_tag (post_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_6ABC1CC44B89032C (post_id), INDEX IDX_6ABC1CC4BAD26311 (tag_id), PRIMARY KEY(post_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE symfony_user (id INT AUTO_INCREMENT NOT NULL, full_name VARCHAR(191) NOT NULL, username VARCHAR(191) NOT NULL, email VARCHAR(191) NOT NULL, password VARCHAR(191) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_4EF5061AF85E0677 (username), UNIQUE INDEX UNIQ_4EF5061AE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE symfony_comment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, author_id INT NOT NULL, content LONGTEXT NOT NULL, published_at DATETIME NOT NULL, INDEX IDX_DF67C3344B89032C (post_id), INDEX IDX_DF67C334F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE symfony_post ADD CONSTRAINT FK_99ECBCDEF675F31B FOREIGN KEY (author_id) REFERENCES symfony_user (id)');
        $this->addSql('ALTER TABLE symfony_demo_post_tag ADD CONSTRAINT FK_6ABC1CC44B89032C FOREIGN KEY (post_id) REFERENCES symfony_post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE symfony_demo_post_tag ADD CONSTRAINT FK_6ABC1CC4BAD26311 FOREIGN KEY (tag_id) REFERENCES symfony_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE symfony_comment ADD CONSTRAINT FK_DF67C3344B89032C FOREIGN KEY (post_id) REFERENCES symfony_post (id)');
        $this->addSql('ALTER TABLE symfony_comment ADD CONSTRAINT FK_DF67C334F675F31B FOREIGN KEY (author_id) REFERENCES symfony_user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE symfony_demo_post_tag DROP FOREIGN KEY FK_6ABC1CC4BAD26311');
        $this->addSql('ALTER TABLE symfony_demo_post_tag DROP FOREIGN KEY FK_6ABC1CC44B89032C');
        $this->addSql('ALTER TABLE symfony_comment DROP FOREIGN KEY FK_DF67C3344B89032C');
        $this->addSql('ALTER TABLE symfony_post DROP FOREIGN KEY FK_99ECBCDEF675F31B');
        $this->addSql('ALTER TABLE symfony_comment DROP FOREIGN KEY FK_DF67C334F675F31B');
        $this->addSql('DROP TABLE symfony_tag');
        $this->addSql('DROP TABLE symfony_post');
        $this->addSql('DROP TABLE symfony_demo_post_tag');
        $this->addSql('DROP TABLE symfony_user');
        $this->addSql('DROP TABLE symfony_comment');
    }
}
