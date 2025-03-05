mod event;
mod table;
mod db;

use event::*;
use db::DB;

use rocket::*;

fn main() {
    let db = DB::new();
    let events = db.read();
    for event in events {
        println!("{:?}", event);
    }
}
