use std::time::SystemTime;

use mysql::prelude::*;
use mysql::*;

use crate::table::Table;
use crate::event::Event;

pub struct DB {
    conn: PooledConn,
}

impl DB {
    pub fn new() -> Self {
        let url = "mysql://root:@localhost:3306/bgszc_events";
        let pool = Pool::new(url).unwrap();
        let conn = pool.get_conn().unwrap();
        DB { conn }
    }

    pub fn read(mut self) -> Vec<Event> {
        let query = "SELECT event_id, name, date, location, busyness FROM event";
        let events: Vec<Event> = self.conn.query_map(query, |(event_id, name, date, location, busyness)| {
            Event {
                event_id,
                name,
                date,
                location,
                busyness,
            }
        }).unwrap();
        events
    }
}
