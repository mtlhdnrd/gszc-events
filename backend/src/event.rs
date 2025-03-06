use crate::table::Table;

use std::time::SystemTime;

use mysql::{prelude::FromValue, Value};
use serde::{Deserialize, Serialize};

#[derive(Debug, Serialize, Deserialize)]
pub enum Busyness {
    Low,
    High,
}

impl From<String> for Busyness {
    fn from(value: String) -> Self {
        match value.as_str() {
            "low" => Busyness::Low,
            "high" => Busyness::High,
            _ => panic!("Invalid busyness value"),
        }
    }
}

impl Into<String> for Busyness {
    fn into(self) -> String {
        match &self {
            Busyness::Low => String::from("low"),
            Busyness::High => String::from("high"),
        }
    }
}

impl FromValue for Busyness {
    type Intermediate = String;

    fn from_value(v: Value) -> Self {
        match v {
            Value::Bytes(bytes) => Busyness::from(String::from_utf8(bytes.to_vec()).unwrap()),
            _ => panic!("Invalid busyness value"),
        }
    }

    fn from_value_opt(v: Value) -> Result<Self, mysql::FromValueError> {
        match v {
            Value::Bytes(bytes) => Ok(Busyness::from(String::from_utf8(bytes.to_vec()).unwrap())),
            _ => Err(mysql::FromValueError(v)),
        }
    }

    fn get_intermediate(v: mysql::Value) -> Result<Self::Intermediate, mysql::FromValueError> {
        match v {
            Value::Bytes(bytes) => Ok(String::from_utf8(bytes.to_vec()).unwrap()),
            _ => Err(mysql::FromValueError(v)),
        }
    }
}

#[derive(Debug, Serialize, Deserialize)]
pub struct Event {
    pub event_id: i32,
    pub name: String,
    pub date: chrono::NaiveDate,
    pub location: String,
    pub busyness: Busyness,
}

impl Table for Event {
    fn create() {
        todo!();
    }

    fn read() {
        todo!();
    }

    fn update() {
        todo!();
    }

    fn delete() {
        todo!();
    }
}
