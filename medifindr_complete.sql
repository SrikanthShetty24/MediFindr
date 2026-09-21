-- ═══════════════════════════════════════════════════════════════════════════════
-- MediFindr — Complete Database Setup (v2 Final)
-- Drop old DB and import this single file.
--
-- Compatible: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+
--
-- Login credentials (all passwords = Admin@123):
--   Admin     : admin@medifindr.com
--   Pharmacy 1: apollo@medifindr.com     (Approved)
--   Pharmacy 2: medplus@medifindr.com    (Approved)
--   Pharmacy 3: wellness@medifindr.com   (Approved)
--   Pharmacy 4: citymed@medifindr.com    (Pending)
--   Pharmacy 5: lifecare@medifindr.com   (Approved)
--   User 1    : john@medifindr.com
--   User 2    : priya@medifindr.com
--   User 3    : rahul@medifindr.com
--   User 4    : anjali@medifindr.com
--   User 5    : irfan@medifindr.com
-- ═══════════════════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS medifindr;
CREATE DATABASE medifindr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medifindr;
SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. ADMIN
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE admin (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. USERS
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    saved_lat       DECIMAL(10,7)  DEFAULT NULL,
    saved_lng       DECIMAL(10,7)  DEFAULT NULL,
    saved_location  VARCHAR(200)   DEFAULT NULL,
    password      VARCHAR(255) NOT NULL,
    is_active     TINYINT(1)   DEFAULT 1,
    reset_token   VARCHAR(64)  DEFAULT NULL,
    reset_expires DATETIME     DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. PHARMACIES
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE pharmacies (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_name  VARCHAR(150) NOT NULL,
    owner_name     VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    phone          VARCHAR(20)  NOT NULL,
    password       VARCHAR(255) NOT NULL,
    address        TEXT         NOT NULL,
    city           VARCHAR(100) NOT NULL,
    state          VARCHAR(100) NOT NULL,
    pincode        VARCHAR(10)  NOT NULL,
    latitude       DECIMAL(10,8) DEFAULT NULL,
    longitude      DECIMAL(11,8) DEFAULT NULL,
    license_number VARCHAR(100) NOT NULL,
    status         ENUM('pending','approved','rejected','inactive') DEFAULT 'pending',
    reset_token    VARCHAR(64)  DEFAULT NULL,
    reset_expires  DATETIME     DEFAULT NULL,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_status (status),
    INDEX idx_city   (city)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. MEDICINES  (v2: added_by_pharmacy_id, is_flagged, flag_reason)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE medicines (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(200) NOT NULL,
    composition          TEXT         NOT NULL,
    dosage               VARCHAR(100) NOT NULL,
    manufacturer         VARCHAR(150) NOT NULL,
    category             VARCHAR(100) DEFAULT 'General',
    description          TEXT,
    added_by_pharmacy_id INT          DEFAULT NULL,
    is_active            TINYINT(1)   DEFAULT 1,
    is_flagged           TINYINT(1)   DEFAULT 0,
    flag_reason          VARCHAR(255) DEFAULT NULL,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name        (name),
    INDEX idx_composition (composition(200)),
    INDEX idx_added_by    (added_by_pharmacy_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. STOCK
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE stock (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT           NOT NULL,
    medicine_id INT           NOT NULL,
    quantity    INT           NOT NULL DEFAULT 0,
    price       DECIMAL(10,2) DEFAULT NULL,
    expiry_date DATE          DEFAULT NULL,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)  ON DELETE CASCADE,
    UNIQUE KEY  uq_pharmacy_medicine (pharmacy_id, medicine_id),
    INDEX idx_medicine (medicine_id),
    INDEX idx_pharmacy (pharmacy_id),
    INDEX idx_quantity (quantity)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 6. SEARCH HISTORY
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE search_history (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    search_term  VARCHAR(255) NOT NULL,
    result_count INT          DEFAULT 0,
    searched_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user        (user_id),
    INDEX idx_searched_at (searched_at)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 7. REPORTED MEDICINES  (v2)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE reported_medicines (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT          NOT NULL,
    user_id     INT          NOT NULL,
    reason      ENUM('wrong_composition','fake_medicine','incorrect_information',
                     'duplicate_medicine','other') NOT NULL DEFAULT 'other',
    details     TEXT         DEFAULT NULL,
    status      ENUM('pending','resolved','dismissed') DEFAULT 'pending',
    admin_note  VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    INDEX idx_medicine (medicine_id),
    INDEX idx_user     (user_id),
    INDEX idx_status   (status)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- 8. MEDICINE MERGE LOG  (v2)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE medicine_merge_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    master_id    INT NOT NULL,
    duplicate_id INT NOT NULL,
    merged_by    INT NOT NULL COMMENT 'admin id',
    merged_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_master    (master_id),
    INDEX idx_duplicate (duplicate_id)
) ENGINE=InnoDB;


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: ADMIN
-- Password hash = Admin@123
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO admin (username, email, password, full_name) VALUES
('admin', 'admin@medifindr.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'System Administrator');


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: USERS  (IDs: 1-5)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO users (full_name, email, phone, password) VALUES
('John Doe',        'john@medifindr.com',   '9876501001', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Priya Nair',      'priya@medifindr.com',  '9876501002', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Rahul Verma',     'rahul@medifindr.com',  '9876501003', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Anjali Singh',    'anjali@medifindr.com', '9876501004', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Mohammed Irfan',  'irfan@medifindr.com',  '9876501005', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: PHARMACIES  (IDs: 1-5)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO pharmacies
    (pharmacy_name, owner_name, email, phone, password, address, city, state, pincode, latitude, longitude, license_number, status)
VALUES
('Apollo Pharmacy Koramangala', 'Rajesh Kumar',
 'apollo@medifindr.com', '9876543210',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '80 Feet Rd, Koramangala 5th Block', 'Bengaluru', 'Karnataka', '560034',
 12.93520000, 77.62450000, 'KA-2024-APL-001', 'approved'),

('MedPlus Indiranagar', 'Priya Sharma',
 'medplus@medifindr.com', '9876543211',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '100 Feet Rd, HAL 2nd Stage, Indiranagar', 'Bengaluru', 'Karnataka', '560038',
 12.97160000, 77.64120000, 'KA-2024-MPL-002', 'approved'),

('Wellness Pharmacy Jayanagar', 'Suresh Gowda',
 'wellness@medifindr.com', '9876543212',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '4th Block, Jayanagar Main Rd', 'Bengaluru', 'Karnataka', '560011',
 12.92490000, 77.58320000, 'KA-2024-WEL-003', 'approved'),

('CityMed Pharmacy Malleshwaram', 'Deepa Rao',
 'citymed@medifindr.com', '9876543213',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '11th Cross, Malleshwaram', 'Bengaluru', 'Karnataka', '560003',
 13.00380000, 77.56960000, 'KA-2024-CMR-004', 'pending'),

('LifeCare Drugs Whitefield', 'Kiran Patel',
 'lifecare@medifindr.com', '9876543214',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Whitefield Main Rd, Kadugodi', 'Bengaluru', 'Karnataka', '560066',
 12.97870000, 77.74600000, 'KA-2024-LCW-005', 'approved');


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: MEDICINES
-- IDs are sequential — mapped exactly in stock inserts below.
-- added_by_pharmacy_id: 1=Apollo 2=MedPlus 3=Wellness 5=LifeCare
-- ═══════════════════════════════════════════════════════════════════════════════

-- ── ANALGESICS / ANTIPYRETICS (IDs 1-5) ─────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Paracetamol 500mg',   'Paracetamol 500mg',                       '1-2 tablets every 4-6 hours. Max 8 tablets/day.',           'Sun Pharma', 'Analgesic/Antipyretic', 'Most common OTC medicine for mild to moderate pain and fever.',        1),
('Crocin 650',          'Paracetamol 650mg',                       '1 tablet every 6-8 hours with water.',                      'GSK',        'Analgesic/Antipyretic', 'Extended-strength paracetamol for stronger fever and body pain.',       2),
('Dolo 650',            'Paracetamol 650mg',                       '1 tablet every 6-8 hours. Max 4 tablets/day.',              'Micro Labs',  'Analgesic/Antipyretic', 'Widely prescribed during viral fever and COVID recovery.',             1),
('Calpol 500',          'Paracetamol 500mg',                       '1-2 tablets every 4-6 hours as needed.',                   'GSK',        'Analgesic/Antipyretic', 'Trusted paracetamol brand for fever and mild pain.',                   3),
('Combiflam',           'Ibuprofen 400mg + Paracetamol 325mg',     '1 tablet twice or thrice daily after food.',               'Sanofi',     'Analgesic/Antipyretic', 'Combination tablet for faster pain and fever relief.',                 2);

-- ── NSAIDs (IDs 6-10) ────────────────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Ibuprofen 400mg',     'Ibuprofen 400mg',                         '1 tablet every 8 hours after food.',                       'Cipla',      'NSAID', 'Anti-inflammatory for pain, fever, and swelling.',                    1),
('Brufen 400',          'Ibuprofen 400mg',                         '1 tablet three times daily with meals.',                   'Abbott',     'NSAID', 'Widely used NSAID for muscle pain and arthritis.',                    2),
('Diclofenac 50mg',     'Diclofenac Sodium 50mg',                  '1 tablet two to three times daily after food.',            'Novartis',   'NSAID', 'Effective for joint pain, dental pain, and inflammation.',            3),
('Voveran 50',          'Diclofenac Sodium 50mg',                  '1 tablet twice daily after meals.',                        'Novartis',   'NSAID', 'Popular brand for musculoskeletal pain and arthritis.',                1),
('Naproxen 500mg',      'Naproxen 500mg',                          '1 tablet twice daily. Take with food.',                    'Cipla',      'NSAID', 'Longer-acting NSAID for arthritis and menstrual pain.',               5);

-- ── ANTIBIOTICS (IDs 11-19) ──────────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Amoxicillin 500mg',   'Amoxicillin 500mg',                       '1 capsule every 8 hours for 5-7 days.',                   'Alembic Pharma','Antibiotic','Broad-spectrum penicillin-type antibiotic for bacterial infections.',1),
('Mox 500',             'Amoxicillin 500mg',                       '1 capsule three times daily.',                             'Ranbaxy',    'Antibiotic','Amoxicillin brand for throat, ear, and chest infections.',           2),
('Azithromycin 500mg',  'Azithromycin 500mg',                      '1 tablet daily for 3-5 days on empty stomach.',            'Cipla',      'Antibiotic','Macrolide antibiotic for respiratory and skin infections.',           1),
('Zithromax 500',       'Azithromycin 500mg',                      '1 tablet once daily for 3 days.',                         'Pfizer',     'Antibiotic','Z-pack brand azithromycin for community-acquired infections.',         3),
('Azee 500',            'Azithromycin 500mg',                      '1 tablet daily for 3 days.',                               'Cipla',      'Antibiotic','Affordable azithromycin for respiratory infections.',                 5),
('Ciprofloxacin 500mg', 'Ciprofloxacin 500mg',                     '1 tablet twice daily for 7-14 days.',                     'Bayer',      'Antibiotic','Fluoroquinolone for urinary tract and gastrointestinal infections.',  2),
('Ciplox 500',          'Ciprofloxacin 500mg',                     '1 tablet every 12 hours.',                                 'Cipla',      'Antibiotic','Broad-spectrum antibiotic for UTIs and typhoid.',                    1),
('Augmentin 625',       'Amoxicillin 500mg + Clavulanic Acid 125mg','1 tablet every 8-12 hours after food.',                  'GSK',        'Antibiotic','Combination antibiotic for resistant bacterial infections.',          3),
('Doxycycline 100mg',   'Doxycycline Hyclate 100mg',               '1 capsule twice daily with plenty of water.',             'Abbott',     'Antibiotic','Tetracycline for acne, malaria prophylaxis, Lyme disease.',           2);

-- ── ANTACID / PPI (IDs 20-25) ────────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Pantoprazole 40mg',   'Pantoprazole 40mg',                       '1 tablet 30 minutes before breakfast daily.',              'Torrent Pharma','Antacid/PPI','Proton pump inhibitor for acid reflux and peptic ulcer.',         1),
('Pan 40',              'Pantoprazole 40mg',                       '1 tablet daily before morning meal.',                      'Alkem',      'Antacid/PPI','Popular brand for GERD, heartburn, and stomach ulcers.',             2),
('Omeprazole 20mg',     'Omeprazole 20mg',                         '1 capsule daily 30 minutes before food.',                  'AstraZeneca', 'Antacid/PPI','First-generation PPI for acid-related disorders.',                  3),
('Omez 20',             'Omeprazole 20mg',                         '1 capsule once daily before breakfast.',                   'Dr. Reddys', 'Antacid/PPI','Widely used omeprazole brand for gastric acidity.',                  1),
('Ranitidine 150mg',    'Ranitidine Hydrochloride 150mg',          '1 tablet twice daily or as directed.',                     'Cipla',      'Antacid/PPI','H2 blocker for acidity, peptic ulcer, and heartburn.',               5),
('Gelusil MPS',         'Aluminium Hydroxide + Magnesium Hydroxide + Simethicone','2 tablets after meals and at bedtime.',     'Pfizer',     'Antacid/PPI','Fast-acting antacid for acidity and gas relief.',                    2);

-- ── ANTIDIABETIC (IDs 26-30) ─────────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Metformin 500mg',     'Metformin Hydrochloride 500mg',           '1 tablet twice daily with meals.',                         'USV Ltd',    'Antidiabetic','First-line oral medication for type 2 diabetes.',                  1),
('Glycomet 500',        'Metformin Hydrochloride 500mg',           '1 tablet twice daily with food.',                          'USV Ltd',    'Antidiabetic','USV brand metformin for blood sugar control.',                      2),
('Metformin 1000mg',    'Metformin Hydrochloride 1000mg',          '1 tablet twice daily with meals.',                         'Sun Pharma', 'Antidiabetic','Higher dose metformin for poorly controlled type 2 diabetes.',      3),
('Glipizide 5mg',       'Glipizide 5mg',                           '1 tablet 30 minutes before breakfast.',                    'Pfizer',     'Antidiabetic','Sulfonylurea for type 2 diabetes — stimulates insulin release.',     1),
('Januvia 100mg',       'Sitagliptin 100mg',                       '1 tablet once daily with or without food.',                'MSD',        'Antidiabetic','DPP-4 inhibitor for type 2 diabetes management.',                  5);

-- ── ANTIHISTAMINES (IDs 31-36) ───────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Cetirizine 10mg',     'Cetirizine Hydrochloride 10mg',           '1 tablet daily at bedtime.',                               'Dr. Reddys', 'Antihistamine','Non-sedating antihistamine for allergic rhinitis and hives.',      1),
('Zyrtec 10',           'Cetirizine Hydrochloride 10mg',           '1 tablet once daily.',                                     'UCB',        'Antihistamine','Well-known cetirizine brand for allergy symptoms.',                 2),
('Cetzine 10',          'Cetirizine Hydrochloride 10mg',           '1 tablet at bedtime.',                                     'Cipla',      'Antihistamine','Affordable cetirizine for hay fever and skin allergies.',           3),
('Loratadine 10mg',     'Loratadine 10mg',                         '1 tablet once daily.',                                     'Cipla',      'Antihistamine','Non-drowsy antihistamine for allergy relief.',                      1),
('Allegra 120mg',       'Fexofenadine Hydrochloride 120mg',        '1 tablet twice daily before meals.',                       'Sanofi',     'Antihistamine','Non-sedating antihistamine for seasonal allergies.',                2),
('Pheniramine 25mg',    'Pheniramine Maleate 25mg',                '1 tablet three times daily.',                              'Torrent Pharma','Antihistamine','First-generation antihistamine for allergies and cold.',          5);

-- ── CARDIOVASCULAR (IDs 37-42) ───────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Amlodipine 5mg',      'Amlodipine Besylate 5mg',                 '1 tablet once daily at the same time each day.',           'Pfizer',     'Cardiovascular','Calcium channel blocker for hypertension and angina.',             1),
('Atorvastatin 10mg',   'Atorvastatin Calcium 10mg',               '1 tablet once daily at bedtime.',                         'Pfizer',     'Cardiovascular','Statin for lowering LDL cholesterol and cardiovascular risk.',      2),
('Lipitor 20mg',        'Atorvastatin Calcium 20mg',               '1 tablet once daily.',                                     'Pfizer',     'Cardiovascular','Higher-dose statin for high cholesterol management.',               3),
('Aspirin 75mg',        'Acetylsalicylic Acid 75mg',               '1 tablet once daily after food.',                         'Bayer',      'Cardiovascular','Low-dose aspirin for antiplatelet therapy and heart attack prevention.',1),
('Losartan 50mg',       'Losartan Potassium 50mg',                 '1 tablet once daily.',                                     'Merck',      'Cardiovascular','ARB for hypertension and diabetic nephropathy.',                    5),
('Metoprolol 25mg',     'Metoprolol Succinate 25mg',               '1 tablet once daily with or after food.',                  'AstraZeneca','Cardiovascular','Beta-blocker for hypertension, angina, and heart failure.',         2);

-- ── RESPIRATORY (IDs 43-47) ──────────────────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Salbutamol 2mg',      'Salbutamol Sulphate 2mg',                 '1 tablet three times daily or as needed.',                 'GSK',        'Respiratory','Bronchodilator for asthma and COPD relief.',                       1),
('Montelukast 10mg',    'Montelukast Sodium 10mg',                 '1 tablet once daily in the evening.',                     'MSD',        'Respiratory','Leukotriene receptor antagonist for asthma and allergic rhinitis.', 2),
('Singulair 10mg',      'Montelukast Sodium 10mg',                 '1 tablet once daily at bedtime.',                         'MSD',        'Respiratory','Brand montelukast for prevention of asthma attacks.',               3),
('Levocetrizine 5mg',   'Levocetirizine Dihydrochloride 5mg',      '1 tablet once daily at night.',                           'UCB',        'Respiratory','Potent antihistamine for allergic rhinitis and chronic urticaria.',  1),
('Dextromethorphan 15mg','Dextromethorphan Hydrobromide 15mg',     '1-2 tablets every 6-8 hours.',                            'Sun Pharma', 'Respiratory','Cough suppressant for dry, non-productive cough.',                  5);

-- ── VITAMINS / SUPPLEMENTS (IDs 48-53) ───────────────────────────────────────
INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id) VALUES
('Vitamin D3 60000 IU', 'Cholecalciferol 60000 IU',               '1 capsule once a week for 8 weeks.',                      'Sun Pharma', 'Vitamin/Supplement','Vitamin D supplement for deficiency correction.',               1),
('Calcium + D3',        'Calcium Carbonate 500mg + Vitamin D3 250 IU','1 tablet twice daily after meals.',                    'Abbott',     'Vitamin/Supplement','Calcium and vitamin D supplement for bone health.',             2),
('Vitamin B12 500mcg',  'Cyanocobalamin 500mcg',                   '1 tablet daily or as directed.',                           'Mankind Pharma','Vitamin/Supplement','B12 supplement for neurological health and anaemia prevention.',3),
('Neurobion Forte',     'Vitamin B1 10mg + B6 3mg + B12 15mcg',   '1 tablet twice daily.',                                    'Merck',      'Vitamin/Supplement','B-complex for nerve health, fatigue, and nutritional deficiency.',1),
('Zinc 50mg',           'Zinc Sulphate 50mg',                      '1 tablet once daily after food.',                         'Cipla',      'Vitamin/Supplement','Zinc supplement for immunity, wound healing, and growth.',       5),
('Iron + Folic Acid',   'Ferrous Sulphate 150mg + Folic Acid 0.5mg','1 tablet once daily on empty stomach.',                  'Torrent Pharma','Vitamin/Supplement','Iron and folate supplement for anaemia and pregnancy.',         2);


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: STOCK
-- Medicine ID map (auto-increment starts at 1):
--  1  Paracetamol 500mg     |  2  Crocin 650            |  3  Dolo 650
--  4  Calpol 500            |  5  Combiflam             |  6  Ibuprofen 400mg
--  7  Brufen 400            |  8  Diclofenac 50mg       |  9  Voveran 50
-- 10  Naproxen 500mg        | 11  Amoxicillin 500mg     | 12  Mox 500
-- 13  Azithromycin 500mg    | 14  Zithromax 500         | 15  Azee 500
-- 16  Ciprofloxacin 500mg   | 17  Ciplox 500            | 18  Augmentin 625
-- 19  Doxycycline 100mg     | 20  Pantoprazole 40mg     | 21  Pan 40
-- 22  Omeprazole 20mg       | 23  Omez 20               | 24  Ranitidine 150mg
-- 25  Gelusil MPS           | 26  Metformin 500mg       | 27  Glycomet 500
-- 28  Metformin 1000mg      | 29  Glipizide 5mg         | 30  Januvia 100mg
-- 31  Cetirizine 10mg       | 32  Zyrtec 10             | 33  Cetzine 10
-- 34  Loratadine 10mg       | 35  Allegra 120mg         | 36  Pheniramine 25mg
-- 37  Amlodipine 5mg        | 38  Atorvastatin 10mg     | 39  Lipitor 20mg
-- 40  Aspirin 75mg          | 41  Losartan 50mg         | 42  Metoprolol 25mg
-- 43  Salbutamol 2mg        | 44  Montelukast 10mg      | 45  Singulair 10mg
-- 46  Levocetrizine 5mg     | 47  Dextromethorphan 15mg | 48  Vitamin D3 60000 IU
-- 49  Calcium + D3          | 50  Vitamin B12 500mcg    | 51  Neurobion Forte
-- 52  Zinc 50mg             | 53  Iron + Folic Acid
-- ═══════════════════════════════════════════════════════════════════════════════

-- Pharmacy 1: Apollo Koramangala
INSERT INTO stock (pharmacy_id, medicine_id, quantity, price, expiry_date) VALUES
(1,  1, 200, 12.50, '2027-06-30'),  -- Paracetamol 500mg
(1,  3, 120, 15.00, '2027-04-30'),  -- Dolo 650
(1,  4, 100, 13.50, '2027-06-30'),  -- Calpol 500
(1,  5,  45, 38.00, '2027-02-28'),  -- Combiflam
(1,  6,  80, 22.00, '2027-03-31'),  -- Ibuprofen 400mg
(1,  9,  55, 48.00, '2026-12-31'),  -- Voveran 50
(1, 11,  60, 85.00, '2027-02-28'),  -- Amoxicillin 500mg
(1, 13,  30, 92.00, '2026-11-30'),  -- Azithromycin 500mg
(1, 16,  25,110.00, '2026-10-31'),  -- Ciprofloxacin 500mg
(1, 17,  20,108.00, '2026-09-30'),  -- Ciplox 500
(1, 20,  95, 45.00, '2027-05-31'),  -- Pantoprazole 40mg
(1, 23,  70, 18.50, '2027-01-31'),  -- Omez 20
(1, 26,  45, 38.00, '2027-06-30'),  -- Metformin 500mg
(1, 29,  20,165.00, '2027-03-31'),  -- Glipizide 5mg
(1, 31,  90, 18.00, '2027-04-30'),  -- Cetirizine 10mg
(1, 34,  45, 68.00, '2027-02-28'),  -- Loratadine 10mg
(1, 37,  35, 95.00, '2027-02-28'),  -- Amlodipine 5mg
(1, 38,  40, 85.00, '2027-01-31'),  -- Atorvastatin 10mg
(1, 40, 150, 12.00, '2027-06-30'),  -- Aspirin 75mg
(1, 43,  60, 45.00, '2027-03-31'),  -- Salbutamol 2mg
(1, 46,  55, 42.00, '2027-04-30'),  -- Levocetrizine 5mg
(1, 48, 110, 28.00, '2027-05-31'),  -- Vitamin D3 60000 IU
(1, 50,  75, 95.00, '2027-04-30'),  -- Vitamin B12 500mcg
(1, 51,  90, 55.00, '2027-06-30');  -- Neurobion Forte

-- Pharmacy 2: MedPlus Indiranagar
INSERT INTO stock (pharmacy_id, medicine_id, quantity, price, expiry_date) VALUES
(2,  2, 150, 14.00, '2027-06-30'),  -- Crocin 650
(2,  3,  80, 15.50, '2027-04-30'),  -- Dolo 650
(2,  5,  50, 37.00, '2027-02-28'),  -- Combiflam
(2,  7,  60, 24.00, '2027-03-31'),  -- Brufen 400
(2, 12,  35, 88.00, '2026-11-30'),  -- Mox 500
(2, 14,  20, 95.00, '2026-09-30'),  -- Zithromax 500
(2, 16,  18,112.00, '2026-10-31'),  -- Ciprofloxacin 500mg
(2, 18,  12,185.00, '2026-08-31'),  -- Augmentin 625
(2, 19,  22, 88.00, '2027-01-31'),  -- Doxycycline 100mg
(2, 21,  85, 48.00, '2027-05-31'),  -- Pan 40
(2, 22,  65, 25.00, '2027-04-30'),  -- Omeprazole 20mg
(2, 25,  60, 21.00, '2027-06-30'),  -- Gelusil MPS
(2, 27,  40, 40.00, '2027-06-30'),  -- Glycomet 500
(2, 32,  75, 20.00, '2027-04-30'),  -- Zyrtec 10
(2, 35,  30,145.00, '2027-01-31'),  -- Allegra 120mg
(2, 38,  35, 87.00, '2027-01-31'),  -- Atorvastatin 10mg
(2, 39,  25,140.00, '2026-12-31'),  -- Lipitor 20mg
(2, 42,  20, 88.00, '2027-03-31'),  -- Metoprolol 25mg
(2, 44,  45,180.00, '2027-01-31'),  -- Montelukast 10mg
(2, 49,  60, 85.00, '2027-05-31'),  -- Calcium + D3
(2, 53,  40, 65.00, '2027-04-30'),  -- Iron + Folic Acid
(2, 24,  55, 32.00, '2027-03-31');  -- Ranitidine 150mg

-- Pharmacy 3: Wellness Jayanagar
INSERT INTO stock (pharmacy_id, medicine_id, quantity, price, expiry_date) VALUES
(3,  1,  90, 12.00, '2027-05-31'),  -- Paracetamol 500mg
(3,  4, 110, 13.00, '2027-06-30'),  -- Calpol 500
(3,  8,  50, 55.00, '2027-02-28'),  -- Diclofenac 50mg
(3, 10,  40, 52.00, '2027-01-31'),  -- Naproxen 500mg
(3, 11,  30, 82.00, '2027-01-31'),  -- Amoxicillin 500mg
(3, 15,  25, 90.00, '2026-12-31'),  -- Azee 500
(3, 18,  15,175.00, '2026-09-30'),  -- Augmentin 625
(3, 20,  85, 44.00, '2027-05-31'),  -- Pantoprazole 40mg
(3, 22,  60, 24.00, '2027-04-30'),  -- Omeprazole 20mg
(3, 28,  30, 95.00, '2027-04-30'),  -- Metformin 1000mg
(3, 31,  60, 17.50, '2027-04-30'),  -- Cetirizine 10mg
(3, 33,  60, 19.00, '2027-04-30'),  -- Cetzine 10
(3, 36,  30, 28.00, '2027-03-31'),  -- Pheniramine 25mg
(3, 39,  20,138.00, '2026-12-31'),  -- Lipitor 20mg
(3, 40,  80, 10.50, '2027-06-30'),  -- Aspirin 75mg
(3, 43,  35, 44.00, '2027-03-31'),  -- Salbutamol 2mg
(3, 45,  30,185.00, '2026-12-31'),  -- Singulair 10mg
(3, 48,  80, 27.00, '2027-05-31'),  -- Vitamin D3 60000 IU
(3, 50,  60, 93.00, '2027-04-30'),  -- Vitamin B12 500mcg
(3, 51,  65, 54.00, '2027-06-30'),  -- Neurobion Forte
(3, 52,  45, 28.00, '2027-04-30'),  -- Zinc 50mg
(3,  6,  55, 21.00, '2027-03-31');  -- Ibuprofen 400mg

-- Pharmacy 5: LifeCare Whitefield
INSERT INTO stock (pharmacy_id, medicine_id, quantity, price, expiry_date) VALUES
(5,  1, 180, 11.50, '2027-06-30'),  -- Paracetamol 500mg
(5,  3,  95, 14.50, '2027-04-30'),  -- Dolo 650
(5,  6,  70, 21.00, '2027-03-31'),  -- Ibuprofen 400mg
(5, 10,  25, 50.00, '2027-02-28'),  -- Naproxen 500mg
(5, 11,  45, 83.00, '2027-01-31'),  -- Amoxicillin 500mg
(5, 15,  30, 89.00, '2026-12-31'),  -- Azee 500
(5, 19,  18, 85.00, '2027-01-31'),  -- Doxycycline 100mg
(5, 20,  75, 43.00, '2027-05-31'),  -- Pantoprazole 40mg
(5, 24,  40, 31.00, '2027-03-31'),  -- Ranitidine 150mg
(5, 25,  30, 22.00, '2027-06-30'),  -- Gelusil MPS
(5, 26,  60, 37.00, '2027-06-30'),  -- Metformin 500mg
(5, 30,  15,285.00, '2027-04-30'),  -- Januvia 100mg
(5, 31,  85, 17.50, '2027-04-30'),  -- Cetirizine 10mg
(5, 35,  25,142.00, '2027-01-31'),  -- Allegra 120mg
(5, 37,  30, 93.00, '2027-02-28'),  -- Amlodipine 5mg
(5, 41,  20,155.00, '2027-03-31'),  -- Losartan 50mg
(5, 43,  40, 44.00, '2027-03-31'),  -- Salbutamol 2mg
(5, 47,  40, 75.00, '2027-05-31'),  -- Dextromethorphan 15mg
(5, 48,  95, 26.00, '2027-05-31'),  -- Vitamin D3 60000 IU
(5, 50,  60, 93.00, '2027-04-30'),  -- Vitamin B12 500mcg
(5, 52,  45, 28.00, '2027-04-30'),  -- Zinc 50mg
(5, 53,  35, 63.00, '2027-04-30');  -- Iron + Folic Acid


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: SEARCH HISTORY  (for admin dashboard trend chart)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO search_history (user_id, search_term, result_count, searched_at) VALUES
(1, 'Paracetamol',    4, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(1, 'Dolo 650',       3, DATE_SUB(NOW(), INTERVAL  1 DAY)),
(1, 'Ibuprofen',      3, DATE_SUB(NOW(), INTERVAL  3 DAY)),
(2, 'Azithromycin',   3, DATE_SUB(NOW(), INTERVAL  1 HOUR)),
(2, 'Cetirizine',     3, DATE_SUB(NOW(), INTERVAL  2 DAY)),
(2, 'Pantoprazole',   2, DATE_SUB(NOW(), INTERVAL  5 DAY)),
(3, 'Metformin',      2, DATE_SUB(NOW(), INTERVAL  4 HOUR)),
(3, 'Amoxicillin',    3, DATE_SUB(NOW(), INTERVAL  6 DAY)),
(4, 'Vitamin D',      2, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 'Ciprofloxacin',  2, DATE_SUB(NOW(), INTERVAL  8 DAY)),
(5, 'Amlodipine',     2, DATE_SUB(NOW(), INTERVAL  3 HOUR)),
(5, 'Atorvastatin',   2, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 'Crocin',         2, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 'Dolo',           3, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(3, 'Aspirin',        2, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(1, 'Paracetamol',    4, DATE_SUB(NOW(), INTERVAL 32 DAY)),
(2, 'Azithromycin',   3, DATE_SUB(NOW(), INTERVAL 38 DAY)),
(3, 'Metformin',      2, DATE_SUB(NOW(), INTERVAL 43 DAY)),
(4, 'Paracetamol',    4, DATE_SUB(NOW(), INTERVAL 50 DAY)),
(5, 'Cetirizine',     3, DATE_SUB(NOW(), INTERVAL 55 DAY)),
(1, 'Ibuprofen',      3, DATE_SUB(NOW(), INTERVAL 62 DAY)),
(2, 'Pantoprazole',   2, DATE_SUB(NOW(), INTERVAL 68 DAY));


-- ═══════════════════════════════════════════════════════════════════════════════
-- SEED: REPORTED MEDICINES  (for admin reports page)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO reported_medicines (medicine_id, user_id, reason, details, status) VALUES
(14, 1, 'duplicate_medicine',
 'Zithromax 500 and Azithromycin 500mg appear to be the same medicine listed twice under different names.',
 'pending'),
(32, 2, 'incorrect_information',
 'Zyrtec 10 dosage says once daily but some prescriptions require twice daily for severe cases. Needs clarification.',
 'pending'),
(24, 3, 'wrong_composition',
 'Ranitidine was recalled in several countries due to NDMA contamination. Please verify this entry is safe.',
 'pending'),
(45, 1, 'duplicate_medicine',
 'Singulair 10mg and Montelukast 10mg are the same active molecule listed as separate medicines.',
 'resolved'),
(39, 4, 'incorrect_information',
 'Lipitor 20mg dosage description appears incomplete compared to official prescribing information.',
 'dismissed');

-- ═══════════════════════════════════════════════════════════════════════════
-- MediFindr — Feature Update Migration
-- Run this on your existing medifindr database
-- Adds: billing system, low-stock notifications, terms tracking
-- ═══════════════════════════════════════════════════════════════════════════


-- ─── FEATURE 2: BILLING SYSTEM ───────────────────────────────────────────────

-- Bills table — one bill per pharmacy visit
CREATE TABLE IF NOT EXISTS bills (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    bill_number     VARCHAR(20)    NOT NULL UNIQUE,
    pharmacy_id     INT            NOT NULL,
    user_id         INT            DEFAULT NULL,        -- NULL = walk-in
    patient_name    VARCHAR(100)   NOT NULL DEFAULT 'Walk-in',
    patient_phone   VARCHAR(20)    DEFAULT NULL,
    subtotal        DECIMAL(10,2)  NOT NULL DEFAULT 0,
    discount_pct    DECIMAL(5,2)   NOT NULL DEFAULT 0,
    discount_amt    DECIMAL(10,2)  NOT NULL DEFAULT 0,
    tax_pct         DECIMAL(5,2)   NOT NULL DEFAULT 0,
    tax_amt         DECIMAL(10,2)  NOT NULL DEFAULT 0,
    total_amount    DECIMAL(10,2)  NOT NULL DEFAULT 0,
    payment_method  ENUM('cash','card','upi','other') DEFAULT 'cash',
    status          ENUM('draft','paid','cancelled')   DEFAULT 'paid',
    notes           TEXT           DEFAULT NULL,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE SET NULL,
    INDEX idx_pharmacy   (pharmacy_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status     (status)
) ENGINE=InnoDB;

-- Bill line items — one row per medicine in a bill
CREATE TABLE IF NOT EXISTS bill_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    bill_id     INT            NOT NULL,
    medicine_id INT            NOT NULL,
    medicine_name VARCHAR(200) NOT NULL,   -- snapshot at billing time
    quantity    INT            NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    line_total  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (bill_id)     REFERENCES bills(id)     ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    INDEX idx_bill     (bill_id),
    INDEX idx_medicine (medicine_id)
) ENGINE=InnoDB;

-- ─── FEATURE 1: LOW STOCK NOTIFICATION LOG ───────────────────────────────────

-- Track when low-stock mails were sent (avoid spamming every minute)
CREATE TABLE IF NOT EXISTS low_stock_notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    medicine_id INT NOT NULL,
    sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pharmacy (pharmacy_id),
    INDEX idx_medicine (medicine_id),
    -- unique per day so pharmacy gets at most one mail per medicine per day
    UNIQUE KEY uq_daily (pharmacy_id, medicine_id, (DATE(sent_at)))
) ENGINE=InnoDB;

-- ─── FEATURE 4: TERMS ACCEPTANCE ─────────────────────────────────────────────

-- Add terms_accepted column to users table
ALTER TABLE users
    ADD COLUMN terms_accepted     TINYINT(1) DEFAULT 0 AFTER is_active,
    ADD COLUMN terms_accepted_at  DATETIME   DEFAULT NULL AFTER terms_accepted;

-- ─── FEATURE 3: USER LOCATION (no schema change needed) ──────────────────────
-- Location is sent client-side via the Geolocation API and passed to
-- api/search.php as lat/lng query params. Distance filtering happens in PHP.
-- No DB column needed — we don't store user GPS for privacy.

-- ─── SAMPLE BILL (for testing) ───────────────────────────────────────────────
-- (Only runs if pharmacies and medicines seed data exists)
INSERT IGNORE INTO bills
    (bill_number, pharmacy_id, patient_name, patient_phone,
     subtotal, discount_pct, discount_amt, tax_pct, tax_amt, total_amount, payment_method, status)
VALUES
    ('BILL-20250001', 1, 'Ravi Kumar', '9876500001',
     185.00, 5.00, 9.25, 0.00, 0.00, 175.75, 'cash', 'paid'),
    ('BILL-20250002', 1, 'Sunita Rao', '9876500002',
     92.00, 0.00, 0.00, 0.00, 0.00, 92.00, 'upi', 'paid'),
    ('BILL-20250003', 2, 'Arun Das', '9876500003',
     340.00, 10.00, 34.00, 0.00, 0.00, 306.00, 'card', 'paid');

INSERT IGNORE INTO bill_items (bill_id, medicine_id, medicine_name, quantity, unit_price, line_total) VALUES
    (1, 1, 'Paracetamol 500mg', 2, 12.50, 25.00),
    (1, 6, 'Ibuprofen 400mg',   5, 22.00, 110.00),
    (1, 20,'Pantoprazole 40mg', 1, 45.00, 45.00),
    (1, 3, 'Dolo 650',          1, 15.00, 15.00),
    (2, 13,'Azithromycin 500mg',1, 92.00, 92.00),
    (3, 48,'Vitamin D3 60000IU',2, 28.00, 56.00),
    (3, 11,'Amoxicillin 500mg', 3, 85.00, 255.00);

-- ═══════════════════════════════════════════════════════════════════════════════
-- Summary
-- ─────────────────────────────────────────────────────────────────────────────
-- Tables          : 8
-- Admin accounts  : 1
-- User accounts   : 5
-- Pharmacies      : 5  (4 approved, 1 pending)
-- Medicines       : 53 across 8 categories
-- Stock entries   : 88 across 4 pharmacies
-- Search history  : 22 entries
-- Reports         : 5  (3 pending, 1 resolved, 1 dismissed)
-- ═══════════════════════════════════════════════════════════════════════════════
