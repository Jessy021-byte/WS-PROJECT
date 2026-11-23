CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  profile_pic VARCHAR(255) DEFAULT NULL,
  id_number VARCHAR(20) DEFAULT NULL,
  program VARCHAR(100) DEFAULT NULL,
  course VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(80) NOT NULL UNIQUE,
  icon VARCHAR(60) NOT NULL DEFAULT 'grid-outline',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  category_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  rental_period ENUM('Hour','Day','Week','Month') NOT NULL DEFAULT 'Day',
  status ENUM('Available','Rented','Unavailable') NOT NULL DEFAULT 'Available',
  image_path VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  owner_id INT NOT NULL,
  renter_id INT NOT NULL,
  status ENUM('requested','rented','completed','cancelled') NOT NULL DEFAULT 'requested',
  rent_period VARCHAR(60),
  rent_price DECIMAL(10,2),
  show_to_owner TINYINT(1) NOT NULL DEFAULT 1,
  show_to_renter TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status_changed_at DATETIME DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  cancelled_at DATETIME DEFAULT NULL,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  owner_id INT NOT NULL,
  renter_id INT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv (item_id, owner_id, renter_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);