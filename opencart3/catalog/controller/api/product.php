<?php
class ControllerApiProduct extends Controller
{
    public function index()
    {
        $this->load->language('api/cart');
        $this->load->model('catalog/product');
        $json             = array();
        $json['products'] = array();
        $filter_data      = array();
        $results          = $this->model_catalog_product->getProducts($filter_data);
        foreach ($results as $result) {
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }
            if ((float) $result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }
            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float) $result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }
            if ($this->config->get('config_review_status')) {
                $rating = (int) $result['rating'];
            } else {
                $rating = false;
            }
            
            if ($result['quantity'] <= 0) {
                $stock        = $this->language->get('0');
                $availability = "outofstock";
            } elseif ($this->config->get('config_stock_display')) {
                $stock        = $result['quantity'];
                $availability = "instock";
            } else {
                $stock        = $this->language->get('1');
                $availability = "instock";
            }
            
            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'name' => $result['name'],
                'model' => $result['model'],
                'price' => $price,
                'availability' => $availability,
                'stock' => $stock,
                'special' => $special,
                'tax' => $tax,
                'rating' => $result['rating'],
                'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'])
            );
        }
        
        
        $json['products'] = $data['products'];
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function edit_product_price()
    {
        
        $json = array();
        
        $product_id = (int) $this->request->post['product_id'];
        $price      = (float) $this->request->post['price'];
        $quantity   = (int) $this->request->post['quantity'];
        
        $query = $this->db->query("SELECT * FROM `oc_product` WHERE `product_id` = {$product_id}");
        
        if ($query->num_rows > 0) {
            $this->db->query("UPDATE oc_product SET price = {$price} , quantity = {$quantity} WHERE product_id = {$product_id} ");
            
            $json['success'] = 'Product updated successfully';
        } else {
            $json['error'] = 'Product not found';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function edit_product_content()
    {
        $json = array();
        $product_id = (int) $this->request->post['product_id'];
        $description      = $this->request->post['description'];
    
        $query = $this->db->query("SELECT * FROM `oc_product_description` WHERE `product_id` = {$product_id}");
    
        if ($query->num_rows > 0) {
            $this->db->query("UPDATE oc_product_description SET description = '{$description}' WHERE product_id = {$product_id} ");
            
            $json['success'] = 'Product updated successfully';
        } else {
            $json['error'] = 'Product not found';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function edit_product_meta_description()
    {
        $json = array();
        $product_id = (int) $this->request->post['product_id'];
        $meta_description      = $this->request->post['meta_description'];
    
        $query = $this->db->query("SELECT * FROM `oc_product_description` WHERE `product_id` = {$product_id}");
    
        if ($query->num_rows > 0) {
            $this->db->query("UPDATE oc_product_description SET meta_description = '{$meta_description}' WHERE product_id = {$product_id} ");
            
            $json['success'] = 'Product updated successfully';
        } else {
            $json['error'] = 'Product not found';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function search_product()
    {
        
        $json = array();
        
        $model = $this->request->post['model'];
        
        $sql = "SELECT * FROM `oc_product` WHERE `model` = '{$model}'";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            foreach ($query->rows as $row) {
                $json = array(
                    'product_id' => $row['product_id'],
                    'model' => $row['model'],
                    'sku' => $row['sku'],
                    'price' => $row['price']
                );
            }
        } else {
            $json = array(
                'text' => 'Not Found'
            );
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        
    }
    public function get_products_category()
    {
        
        $json = array();
        
        $name = $this->request->post['name'];
        
        $sql = "SELECT pc.product_id, cd.name, pd.model, pd.quantity, pd.status, pd.price, pd.sku, pd.upc, pd.ean, pd.jan, pd.isbn, pd.mpn FROM oc_product_to_category pc INNER JOIN oc_category_description cd ON pc.category_id = cd.category_id INNER JOIN oc_product pd ON pc.product_id = pd.product_id WHERE cd.name = '{$name}'";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            foreach ($query->rows as $row) {
                $json[] = array(
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'model' => $row['model'],
                    'sku' => $row['sku'],
                    'upc' => $row['upc'],
                    'ean' => $row['ean'],
                    'jan' => $row['jan'],
                    'isbn' => $row['isbn'],
                    'mpn' => $row['mpn'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'status' => $row['status']
                );
            }
        } else {
            $json = array(
                'text' => 'Not Found'
            );
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        
    }
    
    public function get_products_manufacturer()
    {
        
        $json = array();
        
        $name = $this->request->post['name'];
        
        $sql = "SELECT pd.product_id, brand.name, pd.model, pd.quantity, pd.status, pd.price, pd.sku, pd.upc, pd.ean, pd.jan, pd.isbn, pd.mpn FROM oc_product pd INNER JOIN oc_manufacturer brand ON brand.manufacturer_id = pd.manufacturer_id WHERE brand.name LIKE '%{$name}%'";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            foreach ($query->rows as $row) {
                $json[] = array(
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'model' => $row['model'],
                    'sku' => $row['sku'],
                    'upc' => $row['upc'],
                    'ean' => $row['ean'],
                    'jan' => $row['jan'],
                    'isbn' => $row['isbn'],
                    'mpn' => $row['mpn'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'status' => $row['status']
                );
            }
        } else {
            $json = array(
                'text' => 'Not Found'
            );
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        
    }
    public function search_product_name()
    {
        
        $json = array();
        
        $name = $this->request->post['name'];
        
        $sql = "SELECT * FROM `oc_product_description` WHERE `name` LIKE '%{$name}%'";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            foreach ($query->rows as $row) {
                $json = array(
                    'product_id' => $row['product_id'],
                    'name' => $row['name']
                );
            }
        } else {
            $json = array(
                'text' => 'Not Found'
            );
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        
    }
}
